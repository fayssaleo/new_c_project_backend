<?php

namespace App\Modules\Claim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Claim\Models\Claim;
use App\Modules\ClaimOrIncidentFile\Models\ClaimOrIncidentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Libs\UploadTrait;

class ClaimController extends Controller
{
    use UploadTrait;

    public function indexClaims(){

        $claim=Claim::select()->where('ClaimOrIncident', "Claim")->get();

        return [
            "payload" => $claim,
            "status" => "200_00"
        ];
    }

    public function indexIncidents(){

        $claim=Claim::select()->where('ClaimOrIncident', "Incident")->get();

        return [
            "payload" => $claim,
            "status" => "200_00"
        ];
    }

    public function get($id){

        $claim=Claim::find($id);
        if(!$claim){
            return [
                "payload" => "The searched row does not exist !",
                "status" => "404_1"
            ];
        }
        else {
            return [
                "payload" => $claim,
                "status" => "200_1"
            ];
        }

    }
    public function treated(Request $request){
        $validator = Validator::make($request->all(), [
            "id" => "required",
        ]);
        if ($validator->fails()) {
            return [
                "payload" => $validator->errors(),
                "status" => "406_2"
            ];
        }
        $claim=Claim::find($request->id);
        if(!$claim){
            return [
                "payload" => "The searched row does not exist !",
                "status" => "404_1"
            ];
        }
        else {
            $claim->status = "On progress";
            $claim->save();

            return [
                "payload" => $claim,
                "status" => "200_1"
            ];
        }

    }

    public function closed(Request $request){
        $validator = Validator::make($request->all(), [
            "id" => "required",
        ]);
        if ($validator->fails()) {
            return [
                "payload" => $validator->errors(),
                "status" => "406_2"
            ];
        }
        $claim=Claim::find($request->id);
        if(!$claim){
            return [
                "payload" => "The searched row does not exist !",
                "status" => "404_1"
            ];
        }
        else {
            $claim->status = "Closed";
            $claim->save();

            return [
                "payload" => $claim,
                "status" => "200_1"
            ];
        }

    }

    public function create(Request $request){

        if ($request->id == 0) {

            $validator = Validator::make($request->all(), [
                //"name" => "required:claims,name",
            ]);
            if ($validator->fails()) {
                return [
                    "payload" => $validator->errors(),
                    "status" => "406_2"
                ];
            }
            $claim=Claim::make($request->all());
            //$claim->status = "On progress";
            if($claim->status=="" || $claim->status==null){
                $claim->status = "On progress";
            }
            $claim->save();
            if($request->file()) {
                for ($i=0;$i<count($request["files"]);$i++){
                    $file=$request["files"][$i];
                    $filename=time()."_".$file->getClientOriginalName();
                    $this->uploadOne($file, config('cdn.claim.path'),$filename,"public_uploads_claim_incident_report");
                    $claimOrIncidentFile=new ClaimOrIncidentFile();
                    $claimOrIncidentFile->filename=$filename;
                    $claimOrIncidentFile->claim_id=$claim->id;
                 
                    $claimOrIncidentFile->save();
                }

            }
            return [
                "payload" => $claim,
                "status" => "200"
            ];
        } else {

            $validator = Validator::make($request->all(), [
                "id" => "required",
            ]);
            if ($validator->fails()) {
                return [
                    "payload" => $validator->errors(),
                    "status" => "406_2"
                ];
            }
            $claim=Claim::find($request->id);
            if (!$claim) {
                return [
                    "payload" => "The searched row does not exist !",
                    "status" => "404_3"
                ];
            }
            if($request->name!=$claim->name){
                if(Claim::where("name",$request->name)->count()>0)
                    return [
                        "payload" => "The claim has been already taken ! ",
                        "status" => "406_2"
                    ];
            }
            $claim->claim_date=$request->claim_date;
            $claim->incident_date=$request->incident_date;
            $claim->ClaimOrIncident=$request->ClaimOrIncident;

            $claim->save();

            if($request->file()) {
                for ($i=0;$i<count($request["files"]);$i++){
                    $file=$request["files"][$i];
                    $filename=time()."_".$file->getClientOriginalName();
                    $this->uploadOne($file, config('cdn.claim.path'),$filename,"public_uploads_claim_incident_report");
                    $claimOrIncidentFile=new ClaimOrIncidentFile();
                    $claimOrIncidentFile->filename=$filename;
                    $claimOrIncidentFile->claim_id=$claim->id;
                    
                    $claimOrIncidentFile->save();
                }

            }
            // delete files
            if (!empty($request["filesDelete"]) && $request["filesDelete"] != null ) {
                for ($i=0;$i<count($request["filesDelete"]);$i++){
                    $claimOrIncidentFile=ClaimOrIncidentFile::find($request["filesDelete"][$i]["id"]);
                    $this->deleteOne(config('cdn.claim.path'),$claimOrIncidentFile->filename);
                    $claimOrIncidentFile->delete();
                }
            }
            return [
                "payload" => $claim,
                "status" => "200"
            ];

        }


    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            "id" => "required",
        ]);
        if ($validator->fails()) {
            return [
                "payload" => $validator->errors(),
                "status" => "406_2"
            ];
        }
        $claim=Claim::find($request->id);
        if (!$claim) {
            return [
                "payload" => "The searched row does not exist !",
                "status" => "404_3"
            ];
        }
        if($request->name!=$claim->name){
            if(Claim::where("name",$request->name)->count()>0)
                return [
                    "payload" => "The claim has been already taken ! ",
                    "status" => "406_2"
                ];
        }
        //add other files

        if($request->file()) {
            for ($i=0;$i<count($request["files"]);$i++){
                $file=$request["files"][$i];
                $filename=time()."_".$file->getClientOriginalName();
                $this->uploadOne($file, config('cdn.claim.path'),$filename,"public_uploads_claim_incident_report");
                $claimOrIncidentFile=new ClaimOrIncidentFile();
                $claimOrIncidentFile->filename=$filename;
                $claimOrIncidentFile->claim_id=$claim->id;
                $claimOrIncidentFile->save();
            }

        }
        // delete files
        if (!empty($request["filesDelete"]) && $request["filesDelete"] != null ) {
            for ($i=0;$i<count($request["filesDelete"]);$i++){
                $claimOrIncidentFile=ClaimOrIncidentFile::find($request["filesDelete"][$i]["id"]);
                $this->deleteOne(config('cdn.claim.path'),$claimOrIncidentFile->filename);
                $claimOrIncidentFile->delete();
            }
        }


        $claim->claim_date=$request->claim_date;
        $claim->incident_date=$request->incident_date;
        $claim->ClaimOrIncident=$request->ClaimOrIncident;

        $claim->save();
        return [
            "payload" => $claim,
            "status" => "200"
        ];
    }

    public function delete(Request $request){
        $claim=Claim::find($request->id);
        if(!$claim){
            return [
                "payload" => "The searched row does not exist !",
                "status" => "404_4"
            ];
        }
        else {
            $claim->delete();
            return [
                "payload" => "Deleted successfully",
                "status" => "200_4"
            ];
        }
    }

}
