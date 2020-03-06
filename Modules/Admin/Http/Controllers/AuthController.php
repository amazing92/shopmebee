<?php


namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\MasterController;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
//use Sentinel;

class AuthController extends MasterController
{
    //function show view login
    public function getSignin(){
        try{
            // Is the user logged in?
            if (Sentinel::check()) {
                return Redirect::route('admin.dashboard');
            }
            // Show the page
            return view('admin::auth.login');
        }catch (\Exception $exception){
            abort('500');
        }
    }

    //function show post login
    public function postSignin(Request $request)
    {
        try {
            // Try to log the user in
            if ($user = Sentinel::authenticate($request->only(['email', 'password']), $request->get('remember-me', false))) {
                // Redirect to the dashboard page
                //Activity log
                activity($user->full_name)
                    ->performedOn($user)
                    ->causedBy($user)
                    ->log('LoggedIn');
                //activity log ends
                return Redirect::route("admin.dashboard")->with('success', trans('admin::auth.signin.success'));
            }
            $this->messageBag->add('email', trans('admin::auth.account_not_found'));
        } catch (NotActivatedException $e) {
            $this->messageBag->add('email', trans('admin::auth.account_not_activated'));
        } catch (ThrottlingException $e) {
            $delay = $e->getDelay();
            $this->messageBag->add('email', trans('admin::auth.account_suspended', compact('delay')));
        }
        // Ooops.. something went wrong
        return Redirect::back()->withInput()->withErrors($this->messageBag);
    }
}
