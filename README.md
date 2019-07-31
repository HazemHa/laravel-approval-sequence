
# Laravel Approval Sequence
you can use this library for approval sequence, so you have many rules and need from them to approve your report by sequence.
# Getting Started

# Install the package via composer
```
composer require hazemha/laravel-approval-sequence
```

# Register the service provider

This package makes use of Laravel's auto-discovery. If you are an using earlier version of Laravel (< 5.4) you will need to manually register the service provider.

Add ApprovalSequence\ApprovalSequenceServiceProvider::class to the providers array in config/app.php.



# Publish configuration
```
php artisan vendor:publish --provider="ApprovalSequence\ApprovalSequenceServiceProvider" --tag="config"
```

# Publish migrations
```
php artisan vendor:publish --provider="ApprovalSequence\ApprovalSequenceServiceProvider" --tag="migrations"
```
# Run migrations
```
php artisan migrate
```

# Setting Up

## Setup Approval Model(s)
Any model you wish to attach to an approval process simply requires the ApprovedEntity trait, for example:
```
use ApprovalSequence\Traits\ApprovedEntity;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use ApprovedEntity;
}

```


## Setup Approver Model(s)
Any other model (not just a user model) can approve models by simply adding the Approver trait to it, for example:
```
use ApprovalSequence\Traits\Approver;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use Approver;
}
```


# Usage
Any model that contains the Required Approval trait may have multiple pending Entities, to access these modifications you can call the Entities method on the approval model:

### Pending()
```
\App\Role::Pending()

```
### isPending
#### check specific Entity is Pending or not , so how it be pending when someone disapprove your entity, and how to be not when all of them approve your entity by the sequence.


```

isPending()
$role = \App\Role::find(2);
   $role->isPending();
```
### PendingOn
####  know the rules or the people who disapprove your entity so return array of role_id => true or false.

```
   PendingOn()
$role = \App\Role::find(2);
   $role->PendingOn();

```
### entity
#### return your entity
```
entity()
$role = \App\Role::find(2);
   $role->entity();
```
### approver
#### return users who approve your entity

```
approver()

$role = \App\Role::find(2);
   $role->approver();
```
### Approved
#### return all entities that approved by the user

```
Approved()
 \Auth::user()->Approved();
 ```
### disApproved()
#### return all entities that disapproved by the user

 ```
disApproved()
 \Auth::user()->disApproved();
 ```
### approve($model)
 #### approve model by user
 ```
approve($model)
\Auth::user()->approve($role);
```
### disapprove($model)
#### disapprove model by user

```
disapprove($model)
\Auth::user()->disapprove($role);
```

