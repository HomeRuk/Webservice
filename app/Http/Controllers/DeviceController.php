<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\Http\Requests;
use App\Http\Requests\DeviceRequest;
use App\Http\Requests\DeviceLocationRequest;
use App\Http\Requests\DeviceThresholdRequest;
use App\Http\Requests\DeviceFCMtokenRequest;
use App\Device;
//use App\Weather;
// user DB::
use DB;

class DeviceController extends Controller {

    public function __construct() {
        $this->middleware('auth', ['except' => ['show', 'updateLocation', 'updateThreshold', 'updateFCMtoken', 'updateMode']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $count = Device::count();
        $device = Device::paginate(50);
        return view('device.index', [
            'count' => $count,
            'devices' => $device,
        ]);// Device/index.blade.php
    }
    
    /**
     * Overview Device 
     * Display count,  
     * @return \Illuminate\Http\Response
     */
    public function overview() {
        $count = Device::count();
        return view('device.overview', [
            'count' => $count,
        ]);// Device/overview.blade.php
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function insert() {
        return view('device.insert');// Device/insert.blade.php
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DeviceRequest $request) {
        $Device = new Device();
        //$Device->name = $request->name;
        //$Device->save();
        $Device->SerialNumber  = $request->SerialNumber;
        $Device->create($request->all()); //$fillable
        $request->session()->flash('status', $Device->SerialNumber);
        return back();
        //return redirect()->action('DeviceController@index');
        //return redirect('device');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $SerialNumber
     * @return \Illuminate\Http\Response
     */
    public function show($SerialNumber) {
        $Dserialnumber = Device::where('SerialNumber', '=', $SerialNumber)->get()->last();
        return view('device.show', [
            'Dserialnumbers' => $Dserialnumber
        ]); // Device/show.blade.php
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $SerialNumber
     * @return \Illuminate\Http\Response
     */
    public function destroy($SerialNumber) {
        //Remove row SerialNumber foreign key
        //$Weather = Weather::where('SerialNumber', $SerialNumber)->delete();
        $device = Device::where('SerialNumber', $SerialNumber)->delete();
        return back();
    }

    // Update Setting Location of Device
    public function updateLocation(DeviceLocationRequest $request) {
        $SerialNumber = $request->SerialNumber;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $sid = $request->sid;
        if ($sid == 'Ruk') {
            $device = Device::where('SerialNumber', '=', $SerialNumber)
                    ->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }
    }

    // Update Setting Threshold of Device
    public function updateThreshold(DeviceThresholdRequest $request) {
        $SerialNumber = $request->SerialNumber;
        $threshold = $request->threshold;
        $sid = $request->sid;
        if ($sid == 'Ruk') {
            $device = Device::where('SerialNumber', '=', $SerialNumber)
                    ->update([
                'threshold' => $threshold,
            ]);
        }
    }

    // Update FCMtoken Device to Database
    public function updateFCMtoken(DeviceFCMtokenRequest $request) {
        $SerialNumber = $request->SerialNumber;
        $FCMtoken = $request->FCMtoken;
        dump($request->FCMtoken);
        $sid = $request->sid;
        if ($FCMtoken === '0')
            $FCMtoken = NULL;
        if ($sid == 'Ruk') {
            $device = Device::where('SerialNumber', '=', $SerialNumber)
                    ->update([
                'FCMtoken' => $FCMtoken,
            ]);
        }
    }

    // Update Mode Device to Database
    public function updateMode(Request $request) {
        $SerialNumber = $request->SerialNumber;
        $mode = $request->mode;
        $sid = $request->sid;
        if ($sid == 'Ruk') {
            $device = Device::where('SerialNumber', '=', $SerialNumber)
                    ->update([
                'mode' => $mode,
            ]);
        }
    }

    // Notification prediction rain
    public static function notificationPredict($SerialNumber, $outputPrediction) {
        $thresholds = DB::select('SELECT threshold FROM device WHERE SerialNumber = ? ', [$SerialNumber]);
        foreach ($thresholds as $threshold) {
            $threshold_target = $threshold->threshold;
        }
        // Compare PercentRainCurrent vs SettingThresholdDevice
        if ($outputPrediction >= $threshold_target) {
            DeviceController::alert($SerialNumber, $outputPrediction);
        }
        //dump($threshold_target);
    }

    // Alert to device
    public static function alert($SerialNumber, $PredictPercent) {
        $FCMtokens = DB::select('SELECT FCMtoken FROM device WHERE SerialNumber = ? ', [$SerialNumber]);
        $api_url = "https://fcm.googleapis.com/fcm/send";
        $server_key = "key=AIzaSyD5QSI1ysohyLh8Yqv3Xcx6_SCsL8WJMLc";
        foreach ($FCMtokens as $FCMtoken) {
            $token_target = $FCMtoken->FCMtoken;
        }
        $color = "#4FC3F7";
        $title = "DooFon";
        $body = "$SerialNumber --> Probability Rain! $PredictPercent %";

        $json = "{
	    \"to\" : \"$token_target\",
            \"collapse_key\" : \"DooFon\",    
	    \"priority\" : \"high\",
	    \"notification\" : {
	      \"body\"  : \"$body\",
	      \"title\" : \"$title\",
              \"sound\"  : \"default\",
	      \"icon\"  : \"ic_launcher\"
	      \"color\" : \"$color\"
	      }
	}";

        $context = stream_context_create(array(
            'http' => array(
                'method' => "POST",
                'header' => "Authorization: " . $server_key . "\r\n" .
                "Content-Type: application/json\r\n",
                'content' => "$json"
            )
        ));

        $response = file_get_contents($api_url, FALSE, $context);

        if ($response === FALSE) {
            die('Error Alert');
        } else {
            echo $response;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $SerialNumber
     * @return \Illuminate\Http\Response
     */
    /*
      public function update(Request $request, $SerialNumber)
      {
      $device = Device::where('SerialNumber', '=', $SerialNumber)
      ->update(
      ['address' => $request->address,
      'latitude' => $request->latitude,
      'longitude' => $request->longitude,
      'threshold' => $request->threshold,]
      );
      }
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($SerialNumber) {
        $device = Device::where('SerialNumber', '=', $SerialNumber)->get()->last();
        return view('device.edit', [
            'devices' => $device
        ]); // Device/show.blade.php
    }

    public function update(Request $request, $SerialNumber) {
        //dump($SerialNumber);
        //$SerialNumber = $request->SerialNumber;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $threshold = $request->threshold;
        $mode = $request->mode;
        $device = Device::where('SerialNumber', '=', $SerialNumber)
                ->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'threshold' => $threshold,
            //'mode' => $mode,
        ]);
        //dump($SerialNumber);
        return redirect()->action('DeviceController@index');
        //return back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /* public function create() {
      //return view('device.create');
      }
     */
}
