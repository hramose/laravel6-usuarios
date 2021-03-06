<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller{

  private $errores;

  public function __construct(){
    $this->errores = [
      'required' => 'El campo es requerido.',
      'email' => 'Email incorrecto',
      'password.min' => 'La contraseña debe tener por lo menos :min caracteres',
      'nuevo.confirmed' => 'Las contraseñas no coinciden',
      'nuevo.min' => 'La contraseña debe tener por lo menos :min caracteres',
    ];
  }

  public function index(){
    return view('profile');
  }

  public function update(Request $request){
    $user = \Auth::user();

    $validaciones = [
      'name' => ['required', 'string', 'max:255', 'unique:users,name,'.$user->id],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
    ];

    $request->validate($validaciones, $this->errores);

    $user->name = $request->input('name');
    $user->email = $request->input('email');
    if(! $user->isDirty()){ return redirect()->route('profile.index')->with("status", 'No se detectaron cambios a realizar'); }
    $user->save();

    return redirect()->route('profile.index')->with("status", 'Datos actualizados');
  }// /update


  public function pass(Request $request){
    $validaciones = [
      'pass1' => [
        'required',
        'string',
        function ($attribute, $value, $fail) {
          if( ! Hash::check( $value, \Auth::user()->password) ){ $fail('Contraseña actual incorrecta'); }
        },
      ],
      'nuevo' => ['required', 'string', 'min:8', 'confirmed'],
    ];

    $validator = Validator::make($request->all(), $validaciones, $this->errores);
     if ($validator->fails()) {
       return redirect(route('profile.update', ['#pass']))->withErrors($validator)->withInput();
     }

    $user = \Auth::user();
    $user->password = bcrypt($request->input('nuevo'));

    activity()->disableLogging();
    $user->save();
    activity()->enableLogging();
    activity()->log('Contraseña actualizada');

    return redirect()->route('profile.index')->with("status", 'Contraseña actualizada');
  }// /pass


  public function update_avatar(Request $request){
    $validaciones = [
      'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ];
    $validator = Validator::make($request->all(), $validaciones, $this->errores);
     if ($validator->fails()) {
       return redirect(route('profile.update', ['#foto']))->withErrors($validator);
     }

    $user = \Auth::user();

    $avatarName = $user->id.'_avatar'.time().'.'.request()->avatar->getClientOriginalExtension();

    $request->avatar->storeAs('avatars', $avatarName);

    $user->avatar = $avatarName;
    $user->save();

    return redirect()->route('profile.index', ['#foto'])->with("status", 'Foto actualizada');
  }// /update_avatar

}
