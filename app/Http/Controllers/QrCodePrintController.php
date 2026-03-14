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
        ]);

        $ids = explode(',', $request->input('ids'));
        $qrCodes = QrCode::whereIn('id', $ids)->get();

        return view('admin.qr.bulk-print', [
            'qrCodes' => $qrCodes,
        ]);
    }
}
