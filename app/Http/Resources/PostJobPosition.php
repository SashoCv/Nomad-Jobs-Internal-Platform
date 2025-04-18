<?php

namespace App\Http\Resources;

use App\Models\Candidate;
use App\Models\File;
use App\Models\Position;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\Cast\Object_;

class PostJobPosition extends JsonResource
{
    public static function getAllPositions()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $allPositions = Position::all();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $allPositions,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'status' => 402,
                'data' => [],
            ]);
        }
    }

    public static function storeFileInStorage($jobPosition, $request)
    {
        $name = Storage::disk('public')->put('jopPosition', $request->file('positionDocument'));
        $jobPosition->positionPath = $name;
        $jobPosition->positionName = $request->file('positionDocument')->getClientOriginalName();
    }

    public static function storeInFilesJobPosition(File $file,Position $jobPosition,Candidate $candidate)
    {
        $file->candidate_id = $candidate->id;
        $file->category_id = 8;
        $file->fileName = $jobPosition->positionName;
        $file->filePath = $jobPosition->positionPath;
        $file->autoGenerated = 1;
        $file->deleteFile = 2;

        $file->save();
    }
    
}
