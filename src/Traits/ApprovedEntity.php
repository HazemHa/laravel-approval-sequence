<?php

namespace ApprovalSequence\Traits;

trait ApprovedEntity
{

    /**
     * roles of approvers this model requires in order
     * to mark the modifications as accepted.
     *
     * @var int
     */
    public $approvedRules = [1];



    public static function Pending()
    {
        // take type and return all record belong for this model
        $pending =  \ApprovalSequence\Models\Entity::Where('entity_type', self::class)->get();
        $PendingEntity =  $pending->map(function ($item) {
            // check every item and check it's pending or not
            if($item->entity && $item->entity->isPending()){
                  return $item->entity;
            }

        });
        //to avoid duplication
        return $PendingEntity->unique();
    }



    private function approvedByRole()
    {
        // know people who approve this item by their role
        $pending =  \ApprovalSequence\Models\Entity::Where('entity_id', $this[$this->primaryKey])->get();
        $approvers =  $pending->map(function ($item) {
            return $item->approver->role_id;
        });
         //to avoid duplication
        return $approvers->unique();
    }
    //check if it's item isPending or not
    public  function isPending()
    {
        // check if model approved by all roles in the model
        return $this->pendingItem($this->approvedByRole());
    }

    public function PendingOn()
    {
        
        $approvers =  $this->approvedByRole();
        $pending = collect();
        //  check if approver role exists on approved rules
        foreach ($this->approvedRules as  $value) {
            $pending->put($value, $approvers->contains($value));
        }
        // return 1 , true ,2 ,true  , 3 ,false
        // that means role num 1, 2 approved but number 3 not approve
        return $pending;
    }

    private function pendingItem($approvers)
    {
        $isPending = false;
        $approvers = $approvers->unique();
        // check if all approved rules have approved by the user
        // for example, the model approved by role 1, 2, 3 that's mean it's not pending
        foreach ($this->approvedRules as  $value) {
            //the approver role were 1, 2, 3 so it will return true reverse it will be false = not pending
            $isPending = $approvers->contains($value);
        }
        // it  will be false that means not one approve it yet then it's pending
        return !$isPending;
    }



  // return the model of AprroveEnitiy
    public function entity()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'entity')->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }
  // return the model of Approver
    public function approver()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'entity')->get();
        $data =  $data->map(function ($item) {
            return $item->approver;
        });
        return $data;
    }

}
