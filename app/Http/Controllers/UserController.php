<?php
namespace App\Http\Controllers;
use App\Models\{ActivityLog,User};
use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash; use Illuminate\Validation\Rule;
class UserController extends Controller {
 public function index(){return view('users.index',['users'=>User::orderBy('username')->get()]);}
 public function create(){return view('users.form',['user'=>new User]);}
 public function store(Request $r){$d=$this->data($r);$u=User::create(['username'=>$d['username'],'first_name'=>$d['first_name']??null,'last_name'=>$d['last_name']??null,'role'=>$d['role'],'password_hash'=>Hash::make($d['password']),'is_active'=>true]);$this->log('CREATE',"Created user '{$u->username}'.",$u);return redirect()->route('users.index')->with('success','User created successfully.');}
 public function edit(User $user){return view('users.form',compact('user'));}
 public function update(Request $r,User $user){$d=$this->data($r,$user);$payload=['username'=>$d['username'],'first_name'=>$d['first_name']??null,'last_name'=>$d['last_name']??null,'role'=>$d['role']];if(!empty($d['password']))$payload['password_hash']=Hash::make($d['password']);$user->update($payload);$this->log('UPDATE',"Updated user '{$user->username}'.",$user);return redirect()->route('users.index')->with('success','User updated successfully.');}
 public function toggle(User $user){if($user->user_id===auth()->id())return back()->with('error','You cannot deactivate your own account.');$user->update(['is_active'=>!$user->is_active]);$this->log('UPDATE',($user->is_active?'Activated':'Deactivated')." user '{$user->username}'.",$user);return back()->with('success','User status updated.');}
 private function data(Request $r,?User $u=null){return $r->validate(['username'=>['required','string','max:100',Rule::unique('users','username')->ignore($u?->user_id,'user_id')],'first_name'=>'nullable|string|max:100','last_name'=>'nullable|string|max:100','role'=>['required',Rule::in(['OWNER','SALES_CLERK'])],'password'=>[$u?'nullable':'required','string','min:6','confirmed']]);}
 private function log($a,$d,$u){ActivityLog::create(['user_id'=>auth()->id(),'module'=>'USER','action'=>$a,'description'=>$d,'reference_type'=>'User','reference_id'=>$u->user_id]);}
}
