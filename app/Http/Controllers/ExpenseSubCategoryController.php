<?php

namespace App\Http\Controllers;

use App\Models\ExpenseParentCategory;
use App\Models\ExpenseSubCategory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class ExpenseSubCategoryController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view child-expense', ['only' => ['index', 'show','SubCategory']]);
        $this->middleware('permission:create child-expense', ['only' => ['store']]);
        $this->middleware('permission:edit child-expense', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete child-expense', ['only' => ['destroy']]);
    }

    public function SubCategory(){

        $MainCategories = ExpenseParentCategory::all();
        return view('expense.sub_expense_category.sub_expense_catergory', compact('MainCategories'));
    }

    public function index()
    {

        $getValue = ExpenseSubCategory::with('mainExpenseCategory')->get();
        
        // Always return an array for consistent JavaScript handling
        return response()->json([
            'status' => 200,
            'message' => $getValue
        ]);
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
                'subExpenseCategoryname' => 'required|string',
                'main_expense_category_id' => 'required|integer',

            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = ExpenseSubCategory::create([
                'subExpenseCategoryname' => $request->subExpenseCategoryname,
                'main_expense_category_id' => $request->main_expense_category_id,
                'subExpenseCategoryCode' => $request->subExpenseCategoryCode,
                'description' => $request->description ?? '',

            ]);

     ;
            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Sub Expense Category Details Created Successfully!"
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
        $getValue = ExpenseSubCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Expense Category Found!"
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
        $getValue = ExpenseSubCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Expense Category Found!"
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
                'subExpenseCategoryname' => 'required|string',
                'main_expense_category_id' => 'required|integer',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = ExpenseSubCategory::find($id);

            if ($getValue) {
                $getValue->update([

                    'subExpenseCategoryname' => $request->subExpenseCategoryname,
                    'main_expense_category_id' => $request->main_expense_category_id,
                    'subExpenseCategoryCode' => $request->subExpenseCategoryCode,
                    'description' => $request->description ?? '',

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Sub Expense Category Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Sub Expense Category Found!"
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
        $getValue = ExpenseSubCategory::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Sub Expense Category Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Expense Category Found!"
            ]);
        }
    }

    /**
     * Get subcategories by parent category ID
     */
    public function getByParentCategory($parentCategoryId)
    {
        $subCategories = ExpenseSubCategory::where('main_expense_category_id', $parentCategoryId)
            ->with('mainExpenseCategory')
            ->get();
        
        return response()->json([
            'status' => 200,
            'data' => $subCategories
        ]);
    }
}
