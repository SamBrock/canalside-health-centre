<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Patient;
use App\Doctor;
use DateTime;

class FrontController extends Controller
{
    function index()
    {
        return redirect('patients');
    }
    function list()
    {
        $patients = Patient::orderBy('lastname')->paginate(10);
        return view('chc/list-view', ['patients' => $patients]);
    }
    function searchNames($searchTerm)
    {
        if ($searchTerm != "none")
        {
            $patients = Patient::where('firstname', 'like', '%'.$searchTerm.'%')
                ->orwhere('lastname', 'like', '%'.$searchTerm.'%')
                ->orderBy('lastname')
                ->get();
            $results_count = count($patients);
            return response()->json(['patients' => $patients]);
        }
        if($searchTerm = "none")
        {
            $patients = Patient::orderBy('lastname')->limit(10)->get();
            return response()->json(['patients' => $patients]);
        }
    }
    function details($patientId)
    {
        $patient = Patient::where("patients.id", $patientId)
            ->select('patients.*', 'doctors.firstname as doctor_firstname', 'doctors.lastname as doctor_lastname')
            ->join('doctors', 'doctors.id', '=', 'patients.doctor_id')
            ->first();
        $from = new DateTime($patient->dob);
        $to   = new DateTime('today');
        $age = $from->diff($to)->y;
        return view('chc/details-view', ['patient' => $patient], ['age' => $age]);
    }
    function register()
    {
        $doctors = Doctor::all();
        return view('chc/register-view', ['doctors' => $doctors]);
    }
    function update($patientId)
    {
        $doctors = Doctor::all();
        $patient = Patient::where("patients.id", $patientId)
            ->select('patients.*', 'patients.id as patient_id', 'doctors.firstname as doctor_firstname', 'doctors.lastname as doctor_lastname')
            ->join('doctors', 'doctors.id', '=', 'patients.doctor_id')
            ->first();
        $day = $patient->dob->format("d");
        return view('chc/update-view', ['patient' => $patient], ['doctors' => $doctors])->with($day);
    }
    function save(Request $request, $action, $patientId)
    {
        $this->validate($request, [
            'firstname' => 'required|regex:/^[a-zA-Z ]+$/|max:50',
            'lastname' => 'required|regex:/^[a-zA-Z ]+$/|max:50',
            'address' => 'required|max:300',
            'postcode' => 'required|max:9',
            'mobile' => 'required|numeric|digits:11',
            'gender' => 'required',
            'year' => 'required|numeric|digits:4|min:1900|max:'.(date('Y')),
            'day' => 'required|numeric|min:1|max:31',
            'month' => 'required|numeric|min:1|max:12',
            'doctor' => 'required|min:1',
        ]);

        if($action === "register")
        {
            $patient = new Patient();
        }
        elseif($action === "update")
        {
            $patient = Patient::find($patientId);
        }
        $patient->firstname = $request->firstname;
        $patient->lastname=$request->lastname;
        $patient->address=$request->address;
        $patient->postcode=$request->postcode;
        $patient->mobile_number=$request->mobile;
        $patient->gender=$request->gender;
        $year=$request->year;
        $month=$request->month;
        $day=$request->day;
        $dob=$year."-".$month."-".$day;
        $patient->dob=$dob;
        $patient->doctor_id=$request->doctor;
        $patient->save();
        if($action === "register")
        {
            return redirect('details'.'/'.$patient->id);
        }
        elseif($action === "update")
        {
            return redirect('details'.'/'.$patientId);
        }
    }
    function delete($patientId)
    {
        $patient = Patient::find($patientId);
        $patient->delete();
        return redirect('patients');
    }
}
