<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $file = new File();

        if ($request->hasFile('file')) {
            Storage::disk('public')->put('files', $request->file('file'));
            $name = Storage::disk('public')->put('files', $request->file('file'));
            $file->filePath = $name;
            $file->fileName = $request->file('file')->getClientOriginalName();
        }

        $file->candidate_id = $request->candidate_id;
        $file->category_id = $request->category_id;

        if ($file->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $file,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => [],
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function downloadFile(File $file)
    {
        $pathToFile = public_path('storage/' . $file->filePath);
        return response()->download($pathToFile);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        //
    }
}
