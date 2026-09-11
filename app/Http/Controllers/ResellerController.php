<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResellerApplicationRequest;
use App\Jobs\SendAdminResellerApplicationEmail;
use App\Jobs\SendResellerApplicationReceivedEmail;
use App\Models\ResellerApplication;
use App\Services\ResellerProgram;

class ResellerController extends Controller
{
    public function show()
    {
        $program = ResellerProgram::fromSettings();

        abort_unless($program->enabled(), 404);

        return view('storefront.reseller', compact('program'));
    }

    public function store(ResellerApplicationRequest $request)
    {
        $application = ResellerApplication::create($request->applicationAttributes());

        dispatch(new SendResellerApplicationReceivedEmail($application));
        dispatch(new SendAdminResellerApplicationEmail($application));

        return redirect()
            ->route('reseller')
            ->with('reseller_application_number', $application->application_number);
    }
}
