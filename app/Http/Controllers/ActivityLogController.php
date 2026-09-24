<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog; use Illuminate\Http\Request;
class ActivityLogController extends Controller { public function index(Request $r){$q=ActivityLog::with('user')->latest('created_at');if($r->filled('module'))$q->where('module',$r->module);if($r->filled('date'))$q->whereDate('created_at',$r->date);return view('activity.index',['logs'=>$q->paginate(30)->withQueryString()]);}}
