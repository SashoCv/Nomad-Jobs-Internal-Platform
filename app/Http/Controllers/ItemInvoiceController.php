<?php

namespace App\Http\Controllers;

use App\Models\ItemInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $itemInvoices = ItemInvoice::all();
            return response()->json($itemInvoices);
        } else {
            return response()->json('You are not authorized to perform this action');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ItemInvoice  $itemInvoice
     * @return \Illuminate\Http\Response
     */
    public function show(ItemInvoice $itemInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ItemInvoice  $itemInvoice
     * @return \Illuminate\Http\Response
     */
    public function edit(ItemInvoice $itemInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ItemInvoice  $itemInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $itemInvoice = ItemInvoice::find($id);
            $itemInvoice->price = $request->price;
            $itemInvoice->total = $request->total;
            $itemInvoice->percentage = $request->percentage;
            $itemInvoice->amount = $request->amount;
            $itemInvoice->items_for_invoices_id = $request->items_for_invoices_id;

            $itemInvoice->save();
            return response()->json(['message' => 'Item invoice updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update item invoice'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ItemInvoice  $itemInvoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItemInvoice $itemInvoice)
    {
        //
    }
}
