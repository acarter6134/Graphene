<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'slug', 'public', 'configuration','icon', 'workflow_version_id', 'unlisted','groups','device'];
    protected $casts = ['configuration' => 'object', 'groups'=>'array','public' => 'boolean','unlisted'=>'boolean'];
    protected $appends = ['version','hidden_xs', 'hidden_sm', 'hidden_md', 'hidden_lg', 'composite_limit'];

    /* Transient Properties not saved in the database */
    public $hidden_xs = false;
    public $hidden_sm = false;
    public $hidden_md = false;
    public $hidden_lg = false;
    public $composite_limit = false;
    
    public function group() {
      return $this->belongsTo(Group::class);
    }
    public function submissions() {
        return $this->hasMany(WorkflowSubmission::class);
    }
    public function workflow() {
      return $this->belongsTo(Workflow::class);
    }
    public function workflowVersion() {
      return $this->belongsTo(WorkflowVersion::class);
    }
    public function user_options() {
      return $this->hasOne(UserOption::class);
    }    
    public function getHiddenXsAttribute() {
        return ($this->device === 1 || $this->device === 2);  
    }
    public function getHiddenSmAttribute() {
        return ($this->device === 1 || $this->device === 4);  
    }
    public function getHiddenMdAttribute() {
        return ($this->device === 3 || $this->device === 4);  
    }
    public function getHiddenLgAttribute() {
        return ($this->device === 3 || $this->device === 4);  
    }
    public function getCompositeLimitAttribute() {
        return (is_array($this->groups) && count($this->groups) > 0);
    }

    public function getVersionAttribute() {
        if(is_null($this->workflow_version_id)){
            $myWorkflowVersion = WorkflowVersion::where('workflow_id','=',$this->workflow_id)->orderBy('created_at', 'desc')->first();
        }else if($this->workflow_version_id == 0){
            $myWorkflowVersion = WorkflowVersion::where('workflow_id','=',$this->workflow_id)->where('stable','=',1)->orderBy('created_at', 'desc')->first();
        }else{
            $myWorkflowVersion = WorkflowVersion::where('id','=',$this->workflow_version_id)->first();
        }
        return $myWorkflowVersion;
    }

    public function findVersion() {
        $myWorkflowVersion = WorkflowVersion::where('id','=',$this->version['id'])->first();

        $this->workflow->code = $myWorkflowVersion->code;
        $this->workflow->version = $myWorkflowVersion->id;
    }
    private function iterate($fields,$afunc,&$data){
        foreach($fields as $field){
            // $stuff[]= $field->name;
            if(isset($data->{$field->name}) && !is_null($data->{$field->name})){
                $afunc($field,$data);
            
                if(isset($field->fields)){
                    $this->iterate($field->fields,$afunc,$data->{$field->name});
                }
            }
        }
    }
    public function reduceFields($afunc, &$data) {
        $this->iterate($this->version->code->form->fields, $afunc,$data);
    }
}
