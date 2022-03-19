<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PlanSave;
use App\Http\Requests\Admin\PlanSort;
use App\Http\Requests\Admin\PlanUpdate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function fetch(Request $request)
    {

        $counts = User::select(
            DB::raw("plan_id"),
            DB::raw("count(*) as count")
        )
            ->where('plan_id', '!=', NULL)
            ->where(function ($query) {
                $query->where('expired_at', '>=', time())
                    ->orWhere('expired_at', NULL);
            })
            ->groupBy("plan_id")
            ->get();
        $plans = Plan::orderBy('sort', 'ASC')->get();
        foreach ($plans as $k => $v) {
            $plans[$k]->count = 0;
            foreach ($counts as $kk => $vv) {
                if ($plans[$k]->id === $counts[$kk]->plan_id) $plans[$k]->count = $counts[$kk]->count;
            }
        }
        return response([
            'data' => $plans
        ]);
    }

    public function save(PlanSave $request)
    {
        $params = $request->validated();
        if ($request->input('id')) {
            $plan = Plan::find($request->input('id'));
            if (!$plan) {
                abort(500, 'Đăng ký không tồn tại ');
            }
            DB::beginTransaction();
            // update user group id and transfer
            try {
                User::where('plan_id', $plan->id)->update([
                    'group_id' => $params['group_id'],
                    'transfer_enable' => $params['transfer_enable'] * 1073741824
                ]);
                $plan->update($params);
            } catch (\Exception $e) {
                DB::rollBack();
                abort(500, 'Lưu thất bại ');
            }
            DB::commit();
            return response([
                'data' => true
            ]);
        }
        if (!Plan::create($params)) {
            abort(500, 'Không tạo được ');
        }
        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if (Order::where('plan_id', $request->input('id'))->first()) {
            abort(500, 'Có đơn đặt hàng theo đăng ký này và không thể bị xóa ');
        }
        if (User::where('plan_id', $request->input('id'))->first()) {
            abort(500, 'Có những người dùng theo đăng ký này không thể bị xóa ');
        }
        if ($request->input('id')) {
            $plan = Plan::find($request->input('id'));
            if (!$plan) {
                abort(500, 'ID đăng ký không tồn tại ');
            }
        }
        return response([
            'data' => $plan->delete()
        ]);
    }

    public function update(PlanUpdate $request)
    {
        $updateData = $request->only([
            'show',
            'renew'
        ]);

        $plan = Plan::find($request->input('id'));
        if (!$plan) {
            abort(500, 'Đăng ký không tồn tại ');
        }

        try {
            $plan->update($updateData);
        } catch (\Exception $e) {
            abort(500, 'Lưu thất bại ');
        }

        return response([
            'data' => true
        ]);
    }

    public function sort(PlanSort $request)
    {
        DB::beginTransaction();
        foreach ($request->input('plan_ids') as $k => $v) {
            if (!Plan::find($v)->update(['sort' => $k + 1])) {
                DB::rollBack();
                abort(500, 'Lưu thất bại ');
            }
        }
        DB::commit();
        return response([
            'data' => true
        ]);
    }
}
