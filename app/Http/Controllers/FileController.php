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

        if ($request->hasFile('file1')) {
            Storage::disk('public')->put('files', $request->file('file1'));
            $name = Storage::disk('public')->put('files', $request->file('file1'));
            $file->filePath1 = $name;
            $file->fileName1 = $request->file('file1')->getClientOriginalName();
        }

        if ($request->hasFile('file2')) {
            Storage::disk('public')->put('files', $request->file('file2'));
            $name = Storage::disk('public')->put('files', $request->file('file2'));
            $file->filePath2 = $name;
            $file->fileName2 = $request->file('file2')->getClientOriginalName();
        }

        if ($request->hasFile('file3')) {
            Storage::disk('public')->put('files', $request->file('file3'));
            $name = Storage::disk('public')->put('files', $request->file('file3'));
            $file->filePath3 = $name;
            $file->fileName3 = $request->file('file3')->getClientOriginalName();
        }

        if ($request->hasFile('file4')) {
            Storage::disk('public')->put('files', $request->file('file4'));
            $name = Storage::disk('public')->put('files', $request->file('file4'));
            $file->filePath4 = $name;
            $file->fileName4 = $request->file('file4')->getClientOriginalName();
        }

        if ($request->hasFile('file5')) {
            Storage::disk('public')->put('files', $request->file('file5'));
            $name = Storage::disk('public')->put('files', $request->file('file5'));
            $file->filePath5 = $name;
            $file->fileName5 = $request->file('file5')->getClientOriginalName();
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
    public function destroy($id)
    {
        $fileDelete = File::findOrFail($id);

        if ($fileDelete->delete()) {
            unlink(storage_path() . '/app/public/' . $fileDelete->filePath);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your file has been deleted!',
            ]);
        }
    }
}
