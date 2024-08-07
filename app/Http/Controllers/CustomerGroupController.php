<?php

namespace App\Http\Controllers;

use App\Models\SellingPriceGroup;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Support\Facades\Validator;
class CustomerGroupController extends Controller
{
    public function customerGroup(){
        $SellingPriceGroups = SellingPriceGroup::all(); // this course come from modal
        return view('contact.customer_group',compact('SellingPriceGroups'));
    }
    public function index()
    {
        $getValue = CustomerGroup::all();
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
                'customerGroupName' => 'required',
                'priceCalculationType' => 'required',
                
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = CustomerGroup::create([
                'customerGroupName' => $request->customerGroupName,
                'priceCalculationType' => $request->priceCalculationType,
                'customer_group_id' => $request->customer_group_id,
                'calculationPercentage' => $request->calculationPercentage ?? '-',
            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New CustomerGroup Details Created Successfully!"
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
        $getValue = CustomerGroup::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such CustomerGroup Found!"
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
        $getValue = CustomerGroup::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such CustomerGroup Found!"
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
               'customerGroupName' => 'required',
                'priceCalculationType' => 'required',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = CustomerGroup::find($id);

            if ($getValue) {
                $getValue->update([
                    'customerGroupName' => $request->customerGroupName,
                    'priceCalculationType' => $request->priceCalculationType,
                    'customer_group_id' => $request->customer_group_id,
                    'calculationPercentage' => $request->calculationPercentage,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old CustomerGroup  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such CustomerGroup Found!"
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
        $getValue = CustomerGroup::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "CustomerGroup Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Customer Found!"
            ]);
        }
    }
}
