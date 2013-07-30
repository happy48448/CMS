<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request) {
    Log::debug('Page load event occurred', array('URL' => Request::url(), 'Headers' => Request::header()));
});

App::after(function($request, $response) {
    if(App::Environment() != 'local') {
        if($response instanceof Illuminate\Http\Response) {
            $output = $response->getOriginalContent();
            
            $filters = array('/<!--([^\[|(<!)].*)/' => '', '/(?<!\S)\/\/\s*[^\r\n]*/' => '');
            
            $output = preg_replace(array_keys($filters), array_values($filters), $output);

            $response->setContent($output);
        }
    }
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

// check if the user is logged in and their access level
Route::filter('auth', function($route, $request, $value) {
    if (!Sentry::check()) {
        Session::flash('error', 'You must be logged in to perform that action.');
        return Redirect::guest(URL::route('account.login'));
    }

    if (!Sentry::getUser()->hasAccess($value)) {
        App::abort(403, ucwords($value).' Permissions Are Required');
    }
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function() {
    if (Auth::check()) return Redirect::intended(URL::route('base'));
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function() {
    if (Session::token() != Input::get('_token')) {
        throw new Illuminate\Session\TokenMismatchException;
    }
});
