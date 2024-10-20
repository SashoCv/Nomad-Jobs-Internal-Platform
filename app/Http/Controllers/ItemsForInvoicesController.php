<?php

namespace App\Http\Controllers;

use App\Models\ItemsForInvoices;
use Illuminate\Http\Request;

class ItemsForInvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $itemsForInvoices = ItemsForInvoices::select('id', 'name')->get();
            return response()->json($itemsForInvoices);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching items for invoices']);
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
     * @param  \App\Models\ItemsForInvoices  $itemsForInvoices
     * @return \Illuminate\Http\Response
     */
    public function show(ItemsForInvoices $itemsForInvoices)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ItemsForInvoices  $itemsForInvoices
     * @return \Illuminate\Http\Response
     */
    public function edit(ItemsForInvoices $itemsForInvoices)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ItemsForInvoices  $itemsForInvoices
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ItemsForInvoices $itemsForInvoices)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ItemsForInvoices  $itemsForInvoices
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItemsForInvoices $itemsForInvoices)
    {
        //
    }
}
