<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponSave;
use App\Http\Requests\Admin\CouponGenerate;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function fetch(Request $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $sortType = in_array($request->input('sort_type'), ['ASC', 'DESC']) ? $request->input('sort_type') : 'DESC';
        $sort = $request->input('sort') ? $request->input('sort') : 'id';
        $builder = Coupon::orderBy($sort, $sortType);
        $total = $builder->count();
        $coupons = $builder->forPage($current, $pageSize)
            ->get();
        return response([
            'data' => $coupons,
            'total' => $total
        ]);
    }

    public function show(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, 'Tham số sai ');
        }
        $coupon = Coupon::find($request->input('id'));
        if (!$coupon) {
            abort(500, 'Phiếu giảm giá không tồn tại ');
        }
        $coupon->show = $coupon->show ? 0 : 1;
        if (!$coupon->save()) {
            abort(500, 'Lưu thất bại ');
        }

        return response([
            'data' => true
        ]);
    }

    public function generate(CouponGenerate $request)
    {
        if ($request->input('generate_count')) {
            $this->multiGenerate($request);
            return;
        }

        $params = $request->validated();
        if (!$request->input('id')) {
            if (!isset($params['code'])) {
                $params['code'] = Helper::randomChar(8);
            }
            if (!Coupon::create($params)) {
                abort(500, 'Không tạo được ');
            }
        } else {
            try {
                Coupon::find($request->input('id'))->update($params);
            } catch (\Exception $e) {
                abort(500, 'Lưu thất bại ');
            }
        }

        return response([
            'data' => true
        ]);
    }

    private function multiGenerate(CouponGenerate $request)
    {
        $coupons = [];
        $coupon = $request->validated();
        $coupon['created_at'] = $coupon['updated_at'] = time();
        unset($coupon['generate_count']);
        for ($i = 0;$i < $request->input('generate_count');$i++) {
            $coupon['code'] = Helper::randomChar(8);
            array_push($coupons, $coupon);
        }
        DB::beginTransaction();
        if (!Coupon::insert($coupons)) {
            DB::rollBack();
            abort(500, 'Thiết lập thất bại ');
        }
        DB::commit();
        $data = "Tên, Loại, Số lượng hoặc Tỷ lệ, Thời gian bắt đầu, Thời gian kết thúc, Thời gian có sẵn, Có sẵn để đăng ký, Mã phiếu giảm giá, Thời gian đã tạo \r\n";
        foreach($coupons as $coupon) {
            $type = ['', 'số lượng ', 'Tỷ lệ '][$coupon['type']];
            $value = ['', ($coupon['value'] / 100),$coupon['value']][$coupon['type']];
            $startTime = date('Y-m-d H:i:s', $coupon['started_at']);
            $endTime = date('Y-m-d H:i:s', $coupon['ended_at']);
            $limitUse = $coupon['limit_use'] ?? 'không giới hạn ';
            $createTime = date('Y-m-d H:i:s', $coupon['created_at']);
            $limitPlanIds = isset($coupon['limit_plan_ids']) ? implode("/", $coupon['limit_plan_ids']) : 'không giới hạn ';
            $data .= "{$coupon['name']},{$type},{$value},{$startTime},{$endTime},{$limitUse},{$limitPlanIds},{$coupon['code']},{$createTime}\r\n";
        }
        echo $data;
    }

    public function drop(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, 'Tham số sai ');
        }
        $coupon = Coupon::find($request->input('id'));
        if (!$coupon) {
            abort(500, 'Phiếu giảm giá không tồn tại ');
        }
        if (!$coupon->delete()) {
            abort(500, 'Không xóa được ');
        }

        return response([
            'data' => true
        ]);
    }
}
