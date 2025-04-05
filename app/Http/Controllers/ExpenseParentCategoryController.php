<?php

namespace App\Http\Controllers;

use App\Models\ExpenseParentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseParentCategoryController extends Controller
{
    
    function __construct()
    {
        $this->middleware('permission:view parent-expense', ['only' => ['index', 'show','mainCategory']]);
        $this->middleware('permission:create parent-expense', ['only' => ['store']]);
        $this->middleware('permission:edit parent-expense', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete parent-expense', ['only' => ['destroy']]);
    }


    public function mainCategory(){
        return view('expense.main_expense');
    }

    public function index()
    {

         $getValue = ExpenseParentCategory::all();
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
                'expenseParentCatergoryName' => 'required|string',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = ExpenseParentCategory::create([
                'expenseParentCatergoryName' => $request->expenseParentCatergoryName,
                'description' => $request->description ?? '',

            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Expense Parent Category Details Created Successfully!"
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
        $getValue = ExpenseParentCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Expense Parent Category Found!"
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
        $getValue = ExpenseParentCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Expense Parent Category Found!"
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
                'expenseParentCatergoryName' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = ExpenseParentCategory::find($id);

            if ($getValue) {
                $getValue->update([

                'expenseParentCatergoryName' => $request->expenseParentCatergoryName,
                'description' => $request->description ?? '',

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Expense Parent Category Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Expense Parent Category Found!"
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
        $getValue = ExpenseParentCategory::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Expense Parent Category Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Expense Parent Category Found!"
            ]);
        }
    }
}
