<?php 

namespace App\Libraries;

use Illuminate\Support\Facades\Log;

class JSExecHelper {

    private $node_path = null;
    private $require = [];

    public function __construct($require=[]) {
        $this->node_path = config('app.node_path');
        $this->require = $require;
    }

    private function fetch_require() {
        $require_commands = '';
        foreach($this->require as $name => $file) {
            if (file_exists(public_path('assets/js/vendor/'.$file))) {
                $require_commands.= 'global["'.$name.'"]=require ("'.public_path('assets/js/vendor/'.$file).'");'."\n";
            }
        }
        return $require_commands;
    }

    function run($code,$obj=null) {
        $process = proc_open($this->node_path,[["pipe", "r"],["pipe", "w"],["pipe", "w"]], $pipes);
        if (is_resource($process)) {
            $stdout = '';
            $js_code = "
// Fetch Includes
".$this->fetch_require()."
// Define Global Wrapper
global.wrapper = {}
global.wrapper.console = {};
// define a new console
var console=(function(oldCons){
    return {
        debug: function(text){
            if (typeof global.wrapper.console.debug === 'undefined') {global.wrapper.console.debug=[];}
            global.wrapper.console.debug.push(text)
        },comment: function(text){
            if (typeof global.wrapper.console.comment === 'undefined') {global.wrapper.console.comment=[];}
            global.wrapper.console.comment.push(text)
        },log: function(text){
            if (typeof global.wrapper.console.log === 'undefined') {global.wrapper.console.log=[];}
            global.wrapper.console.log.push(text)
        },info: function (text) {
            if (typeof global.wrapper.console.info === 'undefined') {global.wrapper.console.info=[];}
            global.wrapper.console.info.push(text)
        },warn: function (text) {
            if (typeof global.wrapper.console.warn === 'undefined') {global.wrapper.console.warn=[];}
            global.wrapper.console.warn.push(text)
        },error: function (text) {
            if (typeof global.wrapper.console.error === 'undefined') {global.wrapper.console.error=[];}
            global.wrapper.console.error.push(text)
        },log_default: function (text) {
            oldCons.log(text);
        }
    };
}(global.console));            
global.console = console;

// Call User Custom Code
global.wrapper.return = function(data){
    ".$code."
}.call(null,".json_encode($obj).")
global.wrapper.success = true;
console.log_default(JSON.stringify(global.wrapper));
process.exit(9);
            ";

            fwrite($pipes[0], $js_code);
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exit_code = proc_close($process);
            if ($exit_code == 9) {
                $response_data = json_decode($stdout,true);
                if (!isset($response_data['return'])) {
                    $response_data['return'] = null;
                } 
                return $response_data;
            } else {
                return ['success'=>false,'error'=>explode("\n",$stderr)];
            }
        } else {
            return ['success'=>false, 'error'=>['Unable to connect to node.js process']];
        }
    }
}