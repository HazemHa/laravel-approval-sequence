<?php

namespace ApprovalSequence\Traits;

trait Approver
{
    public $unAuthorizedMessage = "you must follow the sequence of approval process";
    public $unApprovedMessage = "it's not approved yet";

    public $approvedMessage = " you've approved this before";

    public $keyOfRole = "role_id";

    public $sortBy = "role_id";





    public function Approved()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'approver')->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }

    public function disApproved()
    {
        $data = $this->morphMany('\ApprovalSequence\Models\Entity', 'approver')
            ->onlyTrashed()->get();
        $data =  $data->map(function ($item) {
            return $item->entity;
        });
        return $data;
    }


    // $related, $name, $type = null, $id = null, $localKey = null


    public function approve($model)
    {
        // if my order is first and no one approved it , so it for me
        // if someone approved it before me , check about in in database.
        // then check my turn;
        if ($this->isAuthorizedToApprove($model)) {

            $model = $this->TransformToPolymorphic($model);
            $user = $this->TransformToPolymorphic($this);
            // insert model on table

            $previousApprove = \ApprovalSequence\Models\Entity::Where('entity_id', $model->id)->with("approver")->get();
            if ($previousApprove->isEmpty() && $this->firstRoleApprove($model->approvedRules)) {
                return $this->addRoleApprove($user, $model);
            }


            if ($this->inCorrectSequence($model->approvedRules, $previousApprove)) {

                return $this->addRoleApprove($user, $model);
            }
        }
        // return message unauthorized to approve this model
        return $this->unAuthorized();
    }


    private function inCorrectSequence($sequenceModel, $previousApprove)
    {
        $isApproveProcess = $sequenceModel[0] < $sequenceModel[sizeof($sequenceModel) - 1];

        if ($previousApprove->isNotEmpty()) {
            $approvers =  $previousApprove->map(function ($item) {
                return $item->approver;
            });

            $sortedRole =  $this->lastRole($approvers);

            if ($sortedRole->isNotEmpty()) {
                foreach ($sequenceModel as $key => $value) {
                    # code...
                    if ($value == $sortedRole->last()->role_id) {
                        if ($isApproveProcess) {
                            $expectRole = $key + 1 == sizeof($sequenceModel) ? $sequenceModel[$key] : $sequenceModel[$key + 1];
                        } else {
                            $expectRole = max($key - 1, 0) == sizeof($sequenceModel) ? $sequenceModel[$key] : $sequenceModel[max($key - 1, 0)];
                        }

                        break;
                    }
                }


                return $expectRole ==  $this[$this->keyOfRole] || $this[$this->keyOfRole] == $sequenceModel[$key];
            }
        }
        return false;
    }
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

        $deletedRows = \ApprovalSequence\Models\Entity::Where([
            ['approver_id', $user->id], ['entity_id', $model->id]
        ])->delete();
        return $deletedRows == 0 ? false : true;
    }

    private function firstRoleApprove($approvedRules)
    {
        return $this[$this->keyOfRole] <=  $approvedRules[0];
    }

    private function lastRole($arrayOfApprovers)
    {
        return $arrayOfApprovers->sortBy($this->sortBy);
    }


    private function unAuthorized()
    {
        return ["message" => $this->unAuthorizedMessage, "code" => 200];
    }
    private function unApproved()
    {
        return ["message" => $this->unApprovedMessage, "code" => 200];
    }

    private function isAuthorizedToApprove($model)
    {
        return in_array($this[$this->keyOfRole], $model->approvedRules);
    }
    private function TransformToPolymorphic($model)
    {
        $type = get_class($model);
        $primaryKey = $model->getKeyName();
        $approvedRules = $model->approvedRules;
        $model = $model->toArray();
        $id = $model[$primaryKey];
        return (object) ['type' => $type, 'id' => $id, 'approvedRules' => $approvedRules];
    }

    private function Approvers($items)
    {
        return  $items->map(function ($item) {
            return $item->approver;
        });
    }
    public function disapprove($model)
    {
        if ($this->isAuthorizedToApprove($model)) {

            $model = $this->TransformToPolymorphic($model);
            $user = $this->TransformToPolymorphic($this);
            // delete record from  the table



            $previousApprove = \ApprovalSequence\Models\Entity::Where('entity_id', $model->id)->with("approver")->get();
            if ($previousApprove->isEmpty()) {

                return $this->unApproved();
            }
            if ($this->inCorrectSequence(array_reverse($model->approvedRules), $previousApprove)) {

                return $this->removeRoleApprove($user, $model);
            }

        }
        // return message unauthorized the table
        return $this->unAuthorized();
    }


}
