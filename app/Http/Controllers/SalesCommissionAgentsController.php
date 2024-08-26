<?php

namespace App\Http\Controllers;

use App\Models\SalesCommissionAgents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesCommissionAgentsController extends Controller
{
    public function SalesCommissionAgents(){

        return view('sales_commission.sales_commission');
    }
    public function index()
    {
         $getValue = SalesCommissionAgents::all();
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
                'sales_commission_percentage' => 'required|numeric|between:0,99.99',
                'first_name' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = SalesCommissionAgents::create([
                'prefix' => $request->prefix ?? "",
                'first_name' => $request->first_name,
                'last_name' => $request->last_name ?? "",
                'email' => $request->email ?? "",
                'contact_number' => $request->contact_number ?? "",
                'sales_commission_percentage' => $request->sales_commission_percentage,
                'description' => $request->description ?? "",
            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Sales Commission Agent Created Successfully!"
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
        $getValue = SalesCommissionAgents::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sales Comision Agents Found!"
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
        $getValue = SalesCommissionAgents::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sales Commission Agents Found!"
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
                'sales_commission_percentage' => 'required|numeric|between:0,99.99',
                'first_name' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = SalesCommissionAgents::find($id);

            if ($getValue) {
                $getValue->update([

                    'prefix' => $request->prefix ?? "",
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name ?? "",
                    'email' => $request->email ?? "",
                    'contact_number' => $request->contact_number ?? "",
                    'sales_commission_percentage' => $request->sales_commission_percentage,
                    'description' => $request->description ?? "",
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Sales Commission Agents Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Variation Title Found!"
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
        $getValue = SalesCommissionAgents::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Sales Commission Agents Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Sales Commission Agents Found!"
            ]);
        }
    }
}
