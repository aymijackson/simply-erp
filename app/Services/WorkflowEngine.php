<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowLog;
use Illuminate\Support\Facades\DB;

class WorkflowEngine
{
    public static function trigger($module, $event, $referenceId)
    {
        $workflows = Workflow::where('module',$module)
            ->where('trigger_event',$event)
            ->where('is_active',1)
            ->get();

        foreach($workflows as $workflow){

            $steps = $workflow->steps()->orderBy('step_order')->get();

            foreach($steps as $step){

                switch($step->action_type){

                    case 'notification':
                        self::sendNotification($workflow,$step,$referenceId);
                        break;

                    case 'create_record':
                        self::createRecord($workflow,$step,$referenceId);
                        break;

                    case 'update_record':
                        self::updateRecord($workflow,$step,$referenceId);
                        break;

                }

            }

        }
    }

    protected static function sendNotification($workflow,$step,$refId)
    {
        WorkflowLog::create([
            'workflow_id'=>$workflow->id,
            'reference_type'=>$step->action_target,
            'reference_id'=>$refId,
            'status'=>'notification_sent',
            'message'=>$step->action_value
        ]);
    }

    protected static function createRecord($workflow,$step,$refId)
    {
        WorkflowLog::create([
            'workflow_id'=>$workflow->id,
            'reference_type'=>$step->action_target,
            'reference_id'=>$refId,
            'status'=>'record_created',
            'message'=>$step->action_value
        ]);
    }

    protected static function updateRecord($workflow,$step,$refId)
    {
        WorkflowLog::create([
            'workflow_id'=>$workflow->id,
            'reference_type'=>$step->action_target,
            'reference_id'=>$refId,
            'status'=>'record_updated',
            'message'=>$step->action_value
        ]);
    }
}