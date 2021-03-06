<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Product;
use Cart;
use Auth;
use Darryldecode\Cart\CartCondition;
use App\Discount;

class CartController extends Controller
{
    public function add($id)
    {
        $product = Product::find($id);
        $userId = Auth::user()->id;
        Cart::session($userId)->add([
            'id' => $product->id,
            'name' => $product->name_product,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [
                'product_id' => $product->id,
                'picture' => $product->picture
            ]
        ]);

        return redirect('/cart');
    }

    public function addToCheckout($id)
    {
        $product = Product::find($id);
        $userId = Auth::user()->id;
        Cart::session($userId)->add([
            'id' => $product->id,
            'name' => $product->name_product,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [
                'product_id' => $product->id
            ]
        ]);

        return redirect('/checkout');
    }

    public function show()
    {
        $categories = Category::all();
        $userId = Auth::user()->id;
        $latest = Product::orderBy('created_at', 'desc')->limit(4)->get();
        $items = Cart::session($userId)->getContent();
        $total = Cart::session($userId)->getTotal();
        $quantity = Cart::session($userId)->getTotalQuantity();

        return view('cart', compact('items', 'categories', 'total', 'quantity', 'latest'));
    }

    public function remove($id)
    {
        $userId = Auth::user()->id;
        Cart::session($userId)->remove($id);

        return redirect('/cart');
    }

    public function checkDiscount(Request $request)
    {
        $this->validate($request, [
            'code' => 'exists:discounts,promo_code|size:15'
        ]);
        $userId = Auth::user()->id;
        $checkCode = Discount::where('promo_code', $request->code)->where('status', 'valid')->first();
        if ($checkCode) {
            $condition = new CartCondition([
                'name' => 'discount',
                'type' => 'discount',
                'target' => 'total',
                'value' => '-' . $checkCode->discount
            ]);
            Cart::session($userId)->condition($condition);
            $code = Discount::find($checkCode->id);
            $code->status = 'invalid';
            $code->save();

            return redirect('/cart');
        } else {
            return redirect('/cart');
        }
    }
}
