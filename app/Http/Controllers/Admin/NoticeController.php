<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NoticeSave;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Support\Facades\Cache;

class NoticeController extends Controller
{
    public function fetch(Request $request)
    {
        return response([
            'data' => Notice::orderBy('id', 'DESC')->get()
        ]);
    }

    public function save(NoticeSave $request)
    {
        $data = $request->only([
            'title',
            'content',
            'img_url'
        ]);
        if (!$request->input('id')) {
            if (!Notice::create($data)) {
                abort(500, 'Lưu thất bại ');
            }
        } else {
            try {
                Notice::find($request->input('id'))->update($data);
            } catch (\Exception $e) {
                abort(500, 'Lưu thất bại ');
            }
        }
        return response([
            'data' => true
        ]);
    }



    public function show(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, 'Tham số sai ');
        }
        $notice = Notice::find($request->input('id'));
        if (!$notice) {
            abort(500, 'Thông báo không tồn tại ');
        }
        $notice->show = $notice->show ? 0 : 1;
        if (!$notice->save()) {
            abort(500, 'Lưu thất bại ');
        }

        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, 'Lỗi tham số ');
        }
        $notice = Notice::find($request->input('id'));
        if (!$notice) {
            abort(500, 'Thông báo không tồn tại ');
        }
        if (!$notice->delete()) {
            abort(500, 'Không xóa được ');
        }
        return response([
            'data' => true
        ]);
    }
}
