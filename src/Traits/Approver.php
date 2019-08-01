<?php

namespace ApprovalSequence\Traits;

trait Approver
{
    // the message shows up when someone breaks the sequence when to approve an entity.
    public $unAuthorizedMessage = "you must follow the sequence of approval process";
    // the message shows up when trying to disapprove the unapproved entity.
    public $unApprovedMessage = "it's not approved yet";
    // when you approve approved entity.
    public $approvedMessage = " you've approved this before";
    // key of the role we use it to distinguish between rules for user
    public $keyOfRole = "role_id";
   // sort role based on properties to check from sequence
    public $sortBy = "role_id";




    //  return all entities that user-approved
    public function Approved()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'approver')->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }
 //  return all entities that user-disapproved
    public function disApproved()
    {
        // return entities from soft delete because I delete the entity when you disapprove it
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'approver')
            ->onlyTrashed()->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }


    // $related, $name, $type = null, $id = null, $localKey = null

    // approve the entity by user
    public function approve($model)
    {
        // if my order is first and no one approved it , so it for me
        // if someone approved it before me , check about in in database.
        // then check my turn;
        
        // check it authorize to approve or not
        if ($this->isAuthorizedToApprove($model)) {
           // transform the model into the entity model  to save it in the table
            $model = $this->TransformToPolymorphic($model);
            $user = $this->TransformToPolymorphic($this);
            // insert model on table
              // check if the model approved before or not
            $previousApprove = \ApprovalSequence\Models\Entity::Where('entity_id', $model->id)->with("approver")->get();
             //if it's not approved before must check to create a new record based on sequence
            // must start from first one.
            if ($previousApprove->isEmpty() && $this->firstRoleApprove($model->approvedRules)) {
                // the first case of record doesn't exist
                return $this->addRoleApprove($user, $model);
            }

              // the second case if a record exists
              // check from the last role to continue on sequence in the model
              // pass our role from the model with the previous approver.
            if ($this->inCorrectSequence($model->approvedRules, $previousApprove)) {

                return $this->addRoleApprove($user, $model);
            }
        }
        // return message unauthorized to approve this model
        return $this->unAuthorized();
    }

   // check if someone approves the model and continuously based on sequence
    private function inCorrectSequence($sequenceModel, $previousApprove)
    {
        //
        //  1 < 3 = true  but 1 < 1 = false
        //
        $isApproveProcess = $sequenceModel[0] < $sequenceModel[sizeof($sequenceModel) - 1];

        if ($previousApprove->isNotEmpty()) {
            // transfrom entity model to Approver model for example User
            $approvers =  $previousApprove->map(function ($item) {
                return $item->approver;
            });
            
                        // sort the users based ok keyOfRole
            $sortedRole =  $this->lastRole($approvers);
              //it there any users here than mean someone has approved it before
            if ($sortedRole->isNotEmpty()) {
                foreach ($sequenceModel as $key => $value) {
                    # check every role it exists to know the last one here who approve the model
                    
                    // when I reach to last one break role and expect the next role
                    if ($value == $sortedRole->last()->role_id) {
                        // 2 = 2
                         // true
                        // $isApproveProcess it's to avoid -1 value for the expected role
                        if ($isApproveProcess) {
                            // true
                             // 1+1 = 2  ==  3 then expect role = 3
                            $expectRole = $key + 1 == sizeof($sequenceModel) ? $sequenceModel[$key] : $sequenceModel[$key + 1];
                        } else {
                             // false
                              // 0 - 1 = -1 after modify = 0 then 0 == 3 ? true then  note I use key here : false then 0
                            $expectRole = max($key - 1, 0) == sizeof($sequenceModel) ? $sequenceModel[$key] : $sequenceModel[max($key - 1, 0)];
                        }

                        break;
                    }
                }
                 
                // the first condition to check I'm in the correct sequence
                //the second condition to know last role loop have stopped on it , just to ensure
                
                return $expectRole ==  $this[$this->keyOfRole] || $this[$this->keyOfRole] == $sequenceModel[$key];
            }
        }
        return false;
    }
    // add a new record to the database and check if it's exit return it if not just create one
    private function addRoleApprove($user, $model)
    {
        $attributes = [
            'approver_type' => $user->type,
            'approver_id' => $user->id,
            'entity_id' => $model->id,
            'entity_type' => $model->type,
        ];

        $entity = \ApprovalSequence\Models\Entity::withTrashed()->where($attributes)->first();

        if (empty($entity)) {

            $entity = \ApprovalSequence\Models\Entity::create($attributes);
        }

        if ($entity->trashed()) {
            \ApprovalSequence\Models\Entity::withTrashed()
                ->where([['entity_id', $model->id], ['approver_id', $user->id]])
                ->restore();
        } else {
            return ["message" => $this->approvedMessage, "code" => 200];
        }


        return $entity;

    }

    private function removeRoleApprove($user, $model)
    {
        // when user disapproves the entity should return true or false and delete it by soft delete

        $deletedRows = \ApprovalSequence\Models\Entity::Where([
            ['approver_id', $user->id], ['entity_id', $model->id]
        ])->delete();
        return $deletedRows == 0 ? false : true;
    }

    private function firstRoleApprove($approvedRules)
    {
        // check the user it's the first one in sequence approval
        return $this[$this->keyOfRole] <=  $approvedRules[0];
    }

    private function lastRole($arrayOfApprovers)
    {
        //sort all user based on role id to know who the last one approve the entity
        return $arrayOfApprovers->sortBy($this->sortBy);
    }

    // return unauthorize message for user
    private function unAuthorized()
    {
        return ["message" => $this->unAuthorizedMessage, "code" => 200];
    }
    // return unzpproved message
    private function unApproved()
    {
        return ["message" => $this->unApprovedMessage, "code" => 200];
    }
   //check it authorized for a user to approve or not
    private function isAuthorizedToApprove($model)
    {
        //  check if it's the role it exists on model or not
        return in_array($this[$this->keyOfRole], $model->approvedRules);
    }
    private function TransformToPolymorphic($model)
    {
        // transform any model to entity model or in a suitable format
        $type = get_class($model);
        $primaryKey = $model->getKeyName();
        $approvedRules = $model->approvedRules;
        $model = $model->toArray();
        $id = $model[$primaryKey];
        return (object) ['type' => $type, 'id' => $id, 'approvedRules' => $approvedRules];
    }

    private function Approvers($items)
    {
        // return all people who approve this item
        return  $items->map(function ($item) {
            return $item->approver;
        });
    }
    // // disapprove the entity by user
    public function disapprove($model)
    {
        if ($this->isAuthorizedToApprove($model)) {

            $model = $this->TransformToPolymorphic($model);
            $user = $this->TransformToPolymorphic($this);
            // delete record from  the table



            $previousApprove = \ApprovalSequence\Models\Entity::Where('entity_id', $model->id)->with("approver")->get();
            if ($previousApprove->isEmpty()) {
               //  return unapproved message because it's not approved yet
                return $this->unApproved();
            }
            // check from the sequence when try to unapprove model
            // for example 3, 2  , 1
            if ($this->inCorrectSequence(array_reverse($model->approvedRules), $previousApprove)) {

                return $this->removeRoleApprove($user, $model);
            }

        }
        // return message unauthorized the table
        return $this->unAuthorized();
    }


}
