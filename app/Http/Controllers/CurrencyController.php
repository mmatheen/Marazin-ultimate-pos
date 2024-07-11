<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{

    public function index()
    {
        $getValue = Currency::all();
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

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [

                'country' => 'required|string',
                'currency' => 'required|string',
                'code' => 'required|string',
                'symbol' => 'required|string',
                'thousand_separator' => 'required|string',
                'decimal_separator' => 'required|string',

            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = Currency::create([

                'country' => $request->country,
                'currency' => $request->currency,
                'code' => $request->code,
                'symbol' => $request->symbol,
                'thousand_separator' => $request->thousand_separator,
                'decimal_separator' => $request->decimal_separator,

            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Currency Details Created Successfully!"
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
        $getValue = Currency::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Currency Found!"
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
        $getValue = Currency::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Currency Found!"
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
                'country' => 'required|string',
                'currency' => 'required|string',
                'code' => 'required|string',
                'symbol' => 'required|string',
                'thousand_separator' => 'required|string',
                'decimal_separator' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = Currency::find($id);

            if ($getValue) {
                $getValue->update([

                'country' => $request->country,
                'currency' => $request->currency,
                'code' => $request->code,
                'symbol' => $request->symbol,
                'thousand_separator' => $request->thousand_separator,
                'decimal_separator' => $request->decimal_separator,

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Currency  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Currency Found!"
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
        $getValue = Currency::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Currency Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Currency Found!"
            ]);
        }
    }
}
