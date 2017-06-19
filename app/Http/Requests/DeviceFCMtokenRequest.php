<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class DeviceFCMtokenRequest extends Request {

     /**
     * The URI to redirect to if validation fails.
     *
     * @var string
     */
    // redirect ถ้าไม่เป็นไปตาม rule 
    protected $redirect = 'device';
    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true; //true ไม่ต้อง login
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'SerialNumber' => 'required|size:10',
            //'FCMtoken' => 'required|string|min:140|max:160'
            'FCMtoken' => 'required|string|max:160',
            'sid' => 'required',
        ];
    }

    public function messages() {
        return [
            //'SerialNumber.required' => 'กรุณากรอกรหัสอุปกรณ์',
        ];
    }
    
    public function forbiddenResponse() {
        return response()->view('errors.503');
    }

}
