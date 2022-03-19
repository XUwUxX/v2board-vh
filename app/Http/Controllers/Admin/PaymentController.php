<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PaymentSave;
use App\Services\PaymentService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function getPaymentMethods()
    {
        $methods = [];
        foreach (glob(base_path('app//Payments') . '/*.php') as $file) {
            array_push($methods, pathinfo($file)['filename']);
        }
        return response([
            'data' => $methods
        ]);
    }

    public function fetch()
    {
        $payments = Payment::all();
        foreach ($payments as $k => $v) {
            $notifyUrl = url("/api/v1/guest/payment/notify/{$v->payment}/{$v->uuid}");
            if ($v->notify_domain) {
                $parseUrl = parse_url($notifyUrl);
                $notifyUrl = $v->notify_domain . $parseUrl['path'];
            }
            $payments[$k]['notify_url'] = $notifyUrl;
        }
        return response([
            'data' => $payments
        ]);
    }

    public function getPaymentForm(Request $request)
    {
        $paymentService = new PaymentService($request->input('payment'), $request->input('id'));
        return response([
            'data' => $paymentService->form()
        ]);
    }

    public function save(Request $request)
    {
        if (!config('v2board.app_url')) {
            abort(500, 'Vui lòng định cấu hình địa chỉ trang web trong cấu hình trang web ');
        }
        if ($request->input('id')) {
            $payment = Payment::find($request->input('id'));
            if (!$payment) abort(500, 'Phương thức thanh toán không tồn tại ');
            try {
                $payment->update($request->input());
            } catch (\Exception $e) {
                abort(500, 'Cập nhật không thành công ');
            }
            return response([
                'data' => true
            ]);
        }
        $params = $request->validate([
            'name' => 'required',
            'icon' => 'nullable',
            'payment' => 'required',
            'config' => 'required',
            'notify_domain' => 'nullable|url'
        ], [
            'name.required' => 'Tên hiển thị không được để trống ',
            'payment.required' => 'Tham số cổng không được để trống ',
            'config.required' => 'Thông số cấu hình không được để trống ',
            'notify_domain.url' => 'Định dạng tên miền thông báo tùy chỉnh không chính xác '
        ]);
        $params['uuid'] = Helper::randomChar(8);
        if (!Payment::create($params)) {
            abort(500, 'Lưu thất bại ');
        }
        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        $payment = Payment::find($request->input('id'));
        if (!$payment) abort(500, 'Phương thức thanh toán không tồn tại ');
        return response([
            'data' => $payment->delete()
        ]);
    }
}
