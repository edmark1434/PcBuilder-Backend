<?php

namespace App\Http\Controllers;

use App\Models\PcPart;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Services\AiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\BrevoEmailServices;


class PcController extends Controller
{
    
    
    public function categoryList()
    {
        $saveCateg = session('category');
        if($saveCateg){
            return response()->json($saveCateg,200);
        }
        $categories = Category::with('categprice')->get()->map(function ($categ) {
            return [
                $categ->name => $categ->categprice->min_price
            ];
        });
        if(!$categories){
            return response()->json(['message' => 'No categories available'],200);
        }
        session(['category' => $categories]);
        return response()->json($categories,200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getPart($id)
    {
        $part = PcPart::where('external_id', $id)->firstOrFail();
        return response()->json($part, 200);
    }

    public function sendForgetPasswordEmail(Request $request)
    {
        $email = $request->input('email');
        $six_digit_code = rand(100000, 999999);

        $brevo = new BrevoEmailServices();
        $brevo->sendResetCode($email, $six_digit_code);

        Cache::put('password_reset_' . $email, $six_digit_code, now()->addMinutes(5));
        return response()->json([
            'message' => 'Password reset email sent',
        ], 200);
    }

    public function verifyResetCode(Request $request)
    {
        $email = $request->input('email');
        $code = $request->input('code');

        $cachedCode = Cache::get('password_reset_' . $email);

        if ($cachedCode && $cachedCode == $code) {
            return response()->json([
                'message' => 'Code verified successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid or expired code',
            ], 400);
        }
    }
}
