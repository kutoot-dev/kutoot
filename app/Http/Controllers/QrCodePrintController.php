<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodePrintController extends Controller
{
    public function bulkPrint(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'layout' => 'required|in:3-across,single',
            'sticker_width' => 'required|numeric',
            'sticker_height' => 'required|numeric',
            'margin' => 'required|numeric',
        ]);

        $ids = explode(',', $request->input('ids'));
        $qrCodes = QrCode::whereIn('id', $ids)->get();

        return view('admin.qr.bulk-print', [
            'qrCodes' => $qrCodes,
            'layout' => $request->input('layout'),
            'width' => $request->input('sticker_width'),
            'height' => $request->input('sticker_height'),
            'margin' => $request->input('margin'),
        ]);
    }
}
