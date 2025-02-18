<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Link;
use App\User;
use App\Group;
use App\GroupComposite;
use App\Visit;

class LinkController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }
    
    public function list_all_links(Request $request) {
        if (Auth::user()->site_admin) {
            $links = Link::whereHas('group', function($q){
                $q->where('site_id','=',config('app.site')->id);
            })->orderBy('group_id','desc')->orderBy('title','desc')->get();
        } else {
            $links = Link::whereHas('group', function($q){
                $q->where('site_id','=',config('app.site')->id)->whereIn('id',Auth::user()->content_admin_groups);
            })->orderBy('group_id','desc')->orderBy('title','desc')->get();
        }
        return $links;
    }

    public function list_by_group(Request $request, Group $group) {
        return Link::where('group','=','group_id')->orderBy('title')->get();
    }

    public function user_index($group_id = null)
    {
        if (!Auth::check()) {
            abort(403); // You must be authenticated to fetch links
        }
        if (!is_null($group_id)) {
            $composite_groups = GroupComposite::where('group_id','=',$group_id)->pluck('composite_group_id')->toArray();
            $common_groups = array_values(array_intersect(array_merge([$group_id],$composite_groups),Auth::user()->groups));
            $links = Link::whereIn('group_id',$common_groups)->orderBy('title')->get();
        } else {
            $links = Link::whereIn('group_id',Auth::user()->groups)->orderBy('title')->get();
        }
        $dedup_links = [];
        $search_garbage = ['https','http',' ','/',':'];
        foreach($links as $link) {
            $dedup_links[str_replace($search_garbage, '',strtolower($link->link.$link->title))] = $link;
        }
        return array_values($dedup_links);
    }

    public function create(Request $request)
    {
        $link = new Link($request->all());
        $link->save();
        return $link;
    }

    public function update(Request $request, Link $link)
    {
        $data = $request->all();
        $link->update($data);
        return $link;
    }

    public function destroy(Link $link)
    {
        if ($link->delete()) {
            return 1;
        }
    }

    public function redirect(Request $request, Link $link) {
        $visit = new Visit([
            'resource_id'=>$link->id,
            'resource_type'=>'link',
        ]);
        if (Auth::check()) {
            $visit->user_id = Auth::user()->id;
        } else {
            $visit->user_id = 0;
        }
        $visit->save();
        return redirect($link->link);
    }
}
