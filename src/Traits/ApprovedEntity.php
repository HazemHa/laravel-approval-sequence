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

       dd( \Approval\Models\Entity::all());
        // (new static)->approvedRules
        $pending =  \ApprovalSequence\Models\Entity::Where('entity_type', self::class)->get();
        $PendingEntity =  $pending->map(function ($item) {
            if($item->entity && $item->entity->isPending()){
                  return $item->entity;
            }

        });
        return $PendingEntity->unique();
    }



    private function approvedByRole()
    {
        $pending =  \ApprovalSequence\Models\Entity::Where('entity_id', $this[$this->primaryKey])->get();
        $approvers =  $pending->map(function ($item) {
            return $item->approver->role_id;
        });
        return $approvers->unique();
    }
    public  function isPending()
    {
        // (new static)->approvedRules
        return $this->pendingItem($this->approvedByRole());
    }

    public function PendingOn()
    {
        $approvers =  $this->approvedByRole();
        $pending = collect();
        foreach ($this->approvedRules as  $value) {
            $pending->put($value, $approvers->contains($value));
        }
        return $pending;
    }

    private function pendingItem($approvers)
    {
        $isPending = false;
        $approvers = $approvers->unique();
        foreach ($this->approvedRules as  $value) {
            $isPending = $approvers->contains($value);
        }
        return !$isPending;
    }




    public function entity()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'entity')->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }

    public function approver()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'entity')->get();
        $data =  $data->map(function ($item) {
            return $item->approver;
        });
        return $data;
    }


    /**
     * Boolean to mark whether or not the approval model should be saved
     * forcefully.
     *
     * @var bool
     */
    private $forcedApprovalUpdate = false;
    /**
     * Return Modification relations via moprhMany.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function modifications()
    {
        return $this->morphMany(config('approval.models.modification', \Approval\Models\Modification::class), 'modifiable');
    }

    /*
        $modificationModel = config('approval.models.modification', \Approval\Models\Modification::class);
*/
}
