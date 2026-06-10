<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Customer-facing account management — profile and order history.
 *
 * All routes protected by EnsureCustomerAuth middleware.
 *
 * Routes (prefix: /store/account, name: website.account.)
 *   GET   /profile          — profile
 *   GET   /profile/edit     — editProfile
 *   PATCH /profile          — updateProfile
 *   GET   /orders           — orders
 *   GET   /orders/{sale}    — orderDetail
 *   PATCH /change-password  — changePassword
 */
class CustomerAccountController extends Controller
{
    private const GUARD = 'customer';

    /* ---------------------------------------------------------------
     |  Profile
     | --------------------------------------------------------------- */

    public function profile()
    {
        $customer = auth(self::GUARD)->user();

        $recentOrders = $customer->sales()
            ->with('lines.product')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('website.account.profile', compact('customer', 'recentOrders'));
    }

    public function editProfile()
    {
        $customer = auth(self::GUARD)->user();
        return view('website.account.edit_profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = auth(self::GUARD)->user();

        $request->validate([
            'name'          => ['required', 'string', 'max:191'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'address_line1' => ['nullable', 'string', 'max:191'],
            'address_line2' => ['nullable', 'string', 'max:191'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'country'       => ['nullable', 'string', 'max:100'],
            'zip_code'      => ['nullable', 'string', 'max:20'],
        ]);

        $customer->update($request->only([
            'name', 'phone', 'alternate_phone',
            'address_line1', 'address_line2',
            'city', 'state', 'country', 'zip_code',
        ]));

        return redirect()->route('website.account.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  Orders
     | --------------------------------------------------------------- */

    public function orders(Request $request)
    {
        $customer = auth(self::GUARD)->user();

        $orders = $customer->sales()
            ->with(['lines.product', 'location'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('website.account.orders', compact('customer', 'orders'));
    }

    public function orderDetail($saleId)
    {
        $customer = auth(self::GUARD)->user();

        // Ensure customers can only see their own orders
        $sale = $customer->sales()
            ->with(['lines.product.media', 'location', 'payments'])
            ->where('id', $saleId)
            ->firstOrFail();

        return view('website.account.order_detail', compact('customer', 'sale'));
    }

    /* ---------------------------------------------------------------
     |  Change Password
     | --------------------------------------------------------------- */

    public function changePassword(Request $request)
    {
        $customer = auth(self::GUARD)->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $customer->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}
