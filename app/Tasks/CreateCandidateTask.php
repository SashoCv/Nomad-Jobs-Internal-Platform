<?php

namespace App\Tasks;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class CreateCandidateTask
{
    public function run($request)
    {
        if (auth()->user()->role_id == 1 || auth()->user()->role_id == 2) {
            $person = new Candidate();

            $person->fill($request->all());

            if ($request->hasFile('personPassport')) {
                $passportPath = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $person->passportPath = $passportPath;
                $person->passportName = $request->file('personPassport')->getClientOriginalName();
            }

            if ($request->hasFile('personPicture')) {
                $picturePath = Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $person->personPicturePath = $picturePath;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

            if ($person->save()) {
                $jobPositionDocument = Position::find($request->position_id);

                if ($jobPositionDocument && $jobPositionDocument->positionPath != null) {
                    $file = new File();
                    $file->candidate_id = $person->id;
                    $file->category_id = 8;
                    $file->fileName = $jobPositionDocument->positionName;
                    $file->filePath = $jobPositionDocument->positionPath;
                    $file->autoGenerated = 1;
                    $file->deleteFile = 2;
                    $file->save();
                }

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $person,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => [],
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => [],
            ]);
        }
    }
}