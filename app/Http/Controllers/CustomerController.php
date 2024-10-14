<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;


class CustomerController extends Controller
{
    public function Customer(){
        return view('contact.customer.customer');
    }

    public function index()
    {
        $getValue = Customer::all();
        if ($getValue->count() > 0) {

            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [

            'prefix' => 'required|string|max:10',  // Add max length if applicable
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile_no' => 'required|numeric|digits_between:10,15',  // Ensure valid mobile number length
            'email' => 'required|email|max:255',  // Use 'email' for proper email format validation
            'contact_id' => 'required|string|max:255',
            'contact_type' => 'required|string|max:255',
            'date' => 'required|string',
            'assign_to' => 'required|string|max:255',
            'opening_balance' => 'required|numeric',  // Ensure opening balance is a valid number

            ]

        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = Customer::create([

                'prefix' => $request->prefix,
                'first_name' => $request->first_name ?? '',
                'last_name' => $request->last_name ?? '',
                'mobile_no' => $request->mobile_no ?? '',
                'email' => $request->email ?? '',
                'contact_id' => $request->contact_id ?? '',
                'contact_type' => $request->contact_type ?? '',
                'date' => $request->date ?? '',
                'assign_to' => $request->assign_to ?? '',
                'opening_balance' => $request->opening_balance ?? '',
            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Customer Details Created Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Something went wrong!"
                ]);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $getValue = Customer::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Customer Found!"
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
        $getValue = Customer::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Customer Found!"
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make(
            $request->all(),
            [

            'prefix' => 'required|string|max:10',  // Add max length if applicable
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile_no' => 'required|numeric|digits_between:10,15',  // Ensure valid mobile number length
            'email' => 'required|email|max:255',  // Use 'email' for proper email format validation
            'contact_type' => 'required|string|max:255',
            'date' => 'required|string',
            'assign_to' => 'required|string|max:255',
            'opening_balance' => 'required|numeric',  // Ensure opening balance is a valid number
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = Customer::find($id);

            if ($getValue) {
                $getValue->update([

                'prefix' => $request->prefix,
                'first_name' => $request->first_name ?? '',
                'last_name' => $request->last_name ?? '',
                'mobile_no' => $request->mobile_no ?? '',
                'email' => $request->email ?? '',
                'contact_type' => $request->contact_type ?? '',
                'date' => $request->date ?? '',
                'assign_to' => $request->assign_to ?? '',
                'opening_balance' => $request->opening_balance ?? '',

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Customer  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Customer Found!"
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $getValue = Customer::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Customer Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Customer Found!"
            ]);
        }
    }

}

