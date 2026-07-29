<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Image;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;

class CustomersController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display all customers
     */
    public function index(Request $request)
    {
        $query = Customers::query();

        $this->applyLikeFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'full_name', 'full_name');
        $this->applyLikeFilter($query, $request, 'feedback', 'feedback');

        $emailPhone = $request->input('email_phone');
        if (is_scalar($emailPhone) && $emailPhone !== '') {
            $escaped = addcslashes((string) $emailPhone, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('email', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->applyEqualsFilter($query, $request, 'contact_method', 'contact_method');

        $consent = $request->input('consent');
        if (is_scalar($consent) && $consent !== '') {
            $query->where('consent', $consent === '1' ? 1 : 0);
        }

        $approve = $request->input('approve');
        if (is_scalar($approve) && $approve !== '') {
            $query->where('approve', $approve === 'Approved' ? 1 : 0);
        }

        $this->resolveSortAndApply($query, $request, ['full_name', 'customer_id', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->with(['careData.care', 'careData.order'])->paginate($perPage);

        $paginated->getCollection()->transform(function ($customer) {
            if ($customer->approved_at) {
                $today = Carbon::now();
                $approvedAt = Carbon::parse($customer->approved_at);
                $expiryDate = $approvedAt->copy()->addMonths(6);

                if ($today->gt($expiryDate)) {
                    $customer->time_remaining = "Expired";
                    $customer->months_remaining = 0;
                    $customer->days_remaining = 0;
                } else {
                    $diff = $today->diff($expiryDate);

                    $customer->months_remaining = $diff->m;
                    $customer->days_remaining = $diff->d;

                    $customer->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                }

                $customer->range_start = $today->toDateTimeString();
                $customer->range_end = $expiryDate->toDateTimeString();
            }

            $this->attachLongestCareMembership($customer);

            return $customer;
        });

        return $this->paginatedResponse($paginated);
    }

    /**
     * All customers, unpaginated, with the SAME query and membership
     * computation as the pre-pagination index() -- preserved byte-for-byte
     * since this endpoint has by far the largest external-consumer surface
     * in this initiative (12 call sites across 10 files as of Batch 10),
     * all of which expect a bare array of fully-computed customer objects.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        $customers = Customers::with(['careData.care', 'careData.order'])
            ->latest()
            ->get()
            ->map(function($customer) {
                if ($customer->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($customer->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

                    if ($today->gt($expiryDate)) {
                        $customer->time_remaining = "Expired";
                        $customer->months_remaining = 0;
                        $customer->days_remaining = 0;
                    } else {
                        $diff = $today->diff($expiryDate);

                        $customer->months_remaining = $diff->m;
                        $customer->days_remaining = $diff->d;

                        $customer->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                    }

                    $customer->range_start = $today->toDateTimeString();
                    $customer->range_end = $expiryDate->toDateTimeString();
                }

                $this->attachLongestCareMembership($customer);

                return $customer;
            });

        return response()->json($customers);
    }

    /**
     * A customer can have QuiviCare coverage from several orders at once
     * (different tiers, different start dates). Reflect the *longest*
     * running coverage -- i.e. the CareData record whose order date + tier
     * period lands furthest in the future -- not just the latest order,
     * since an earlier order on a longer tier (e.g. VIS10N/10yr) can easily
     * outlast a more recent one on a shorter tier (e.g. COR3/3yr).
     */
    private function attachLongestCareMembership($customer)
    {
        $longest = $customer->careData
            ->filter(fn($careData) => $careData->membership_expiry_date)
            ->sortByDesc(fn($careData) => $careData->membership_expiry_date)
            ->first();

        $customer->care_membership_tier = optional(optional($longest)->care)->name;
        $customer->care_membership_active = $longest ? $longest->membership_active : false;
        $customer->care_membership_expiry = optional(optional($longest)->membership_expiry_date)->toDateString();
        $customer->care_membership_remaining = $longest ? $longest->membership_remaining : 'N/A';
        $customer->care_membership_order_id = optional($longest)->order_id;

        $customer->unsetRelation('careData');
    }

    public function getActiveCustomers()
    {
        // Fetch customers where today is less than (approved_at + 6 months)
        // This is mathematically: approved_at > (today - 6 months)
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $customers = Customers::where('approved_at', '>', $sixMonthsAgo)
                            ->latest()
                            ->get();

        return response()->json($customers);
    }

    public function show($id)
    {
        return response()->json(
            Customers::findOrFail($id)
        );
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name'        => 'nullable|max:255',
            'preferred_name'   => 'required|max:255',
            'email'            => 'nullable|email|unique:customers,email',
            'phone'            => 'nullable|max:20|unique:customers,phone',
            'address'          => 'nullable',
            'contact_method'   => 'nullable',
            'contact_other'    => 'nullable|max:255',
            'feedback'         => 'nullable|string',
            'hear_about'       => 'nullable|string',
            'hear_about_other' => 'nullable|string',
            'referred_by'      => 'nullable|string',
            'consent'          => 'nullable|boolean',
            'plan'             => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $customerId = BusinessId::next('customers', 'customer_id', 'QV-CUST-', 6);

            $customer = Customers::create([
                'customer_id'      => $customerId,
                'full_name'        => $request->full_name,
                'preferred_name'   => $request->preferred_name,
                'email'            => $request->email,
                'phone'            => $request->phone,
                'address'          => $request->address,
                'contact_method'   => $request->contact_method,
                'contact_other'    => $request->contact_other,
                'feedback'         => $request->feedback,
                'hear_about'       => $request->hear_about,
                'hear_about_other' => $request->hear_about_other,
                'referred_by'      => $request->referred_by,
                'consent'          => $request->consent,
                'plan'          => $request->plan,
            ]);

            // 🔐 GENERATE ONE-TIME UPDATE TOKEN
            $customer->update_token = Str::uuid();
            $customer->update_used  = false;
            $customer->save();

            DB::commit();

            return response()->json([
                'message'     => 'Customer registered successfully',
                'customer_id' => $customerId,
                'update_link' => url('/customer/update/' . $customer->update_token)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $customer = Customers::findOrFail($id);

        $request->validate([
            'full_name'        => 'required|max:255',
            'preferred_name'   => 'required|max:255',
            'email'            => 'required|email|unique:customers,email,' . $customer->id,
            'phone'            => 'required|max:20|unique:customers,phone,' . $customer->id,
            'address'          => 'required',
            'contact_method'   => 'required',
            'contact_other'    => 'nullable|max:255',
            'feedback'         => 'nullable|string',
            'hear_about'       => 'nullable|string',
            'hear_about_other' => 'nullable|string',
            'referred_by'      => 'nullable|string',
            'consent'          => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            // Handle photo update
            if ($request->photo && Str::startsWith($request->photo, 'data:image')) {
                $customer->photo = $this->savePhoto($request->photo);
            }

            $customer->update($request->except('photo'));

            DB::commit();

            return response()->json([
                'message' => 'Customer updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    Public function updateApprove(Request $request, $id)
    {
        $request->validate([
            'approve' => 'required|string',
        ]);

        $approveValue = $request->approve === 'Approved' ? 1 : 0;

        $customer = Customers::findOrFail($id);

        $customer->update([
            'approve' => $approveValue,
            'approved_at' => $approveValue ? now() : null,
        ]);

        return response()->json([
            'message' => 'Approval updated successfully',
            'approve' => $approveValue,
        ]);
    }

    public function destroy($id)
    {
        $customer = Customers::findOrFail($id);

        $customer->delete(); // Soft delete (sets deleted_at)

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }

    public function generateUpdateLink($id)
    {
        $customer = Customers::findOrFail($id);

        if (!$customer->update_token || $customer->update_used) {
            $customer->update_token = Str::uuid();
            $customer->update_used = false;
            $customer->save();
        }

        return response()->json([
            'update_link' => url('/customer/public/' . $customer->update_token)
        ]);
    }

    public function publicShow($token)
    {
        $customer = Customers::where('update_token', $token)
            ->where('update_used', false)
            ->firstOrFail();

        if (!$customer) {
            return response()->json([
                'message' => 'This update link is invalid or already used.'
            ], 403);
        }

        return response()->json($customer);
    }

     public function publicUpdate(Request $request, $token)
    {
        $customer = Customers::where('update_token', $token)
            ->where('update_used', false)
            ->firstOrFail();

        if (!$customer) {
            return response()->json([
                'message' => 'This update link is invalid or already used.'
            ], 403);
        }

        $request->validate([
            'full_name' => 'required|string',
            'preferred_name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
        ]);

        $customer->update([
            'full_name' => $request->full_name,
            'preferred_name' => $request->preferred_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'contact_method' => $request->contact_method,
            'contact_other' => $request->contact_other,
            'feedback' => $request->feedback,
            'hear_about' => $request->hear_about,
            'hear_about_other' => $request->hear_about_other,
            'referred_by' => $request->referred_by,
            'update_used' => true,
            'consent' => $request->consent,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your information has been updated successfully.'
        ]);
    }

}
