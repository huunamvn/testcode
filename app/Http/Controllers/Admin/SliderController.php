<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSliderRequest;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Models\Category;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SliderController extends Controller
{
    public function index(Request $request, $category_id)
    {
        $search = $request->input('search');
        if ($category_id != 'trash') {
            $sliders = Slider::with('category')
                ->where('category_id', $category_id)
                ->when($search, function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->orderBy('position', 'asc')
                ->paginate(10);
            return view('admin.slider.index', compact('sliders', 'search', 'category_id'));
        } else {
            $sliders = Slider::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->paginate(10);
            return view('admin.slider.trash', compact('sliders', 'search', 'category_id'));
        }


    }

    public function updateOrder(Request $request)
    {
        $order = $request->input('order');  // Mảng ID của các slider theo thứ tự mới
        $category_id = $request->input('category_id');  // ID của danh mục hiện tại

        // Kiểm tra dữ liệu đầu vào có hợp lệ không
        if (!$order || !$category_id) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ'], 400);
        }

        // Kiểm tra xem danh mục có tồn tại không
        $category = Category::findOrFail($category_id);

        // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::transaction(function () use ($order, $category_id) {

            // Đặt tạm thời tất cả các 'position' về giá trị âm để tránh xung đột khi cập nhật
            Slider::where('category_id', $category_id)->update(['position' => null]);

            // Duyệt qua từng slider và cập nhật lại thứ tự (position) chính xác
            foreach ($order as $position => $id) {
                Slider::where('id', $id)
                    ->where('category_id', $category_id)
                    ->update(['position' => $position + 1]);
            }
        });

        return response()->json(['message' => 'Cập nhật thứ tự thành công!']);
    }


    public function create()
    {
        $categories = Category::whereNull('parent_id')->get();
        return view('admin.slider.create', compact('categories'));
    }

    public function store(StoreSliderRequest $request)
    {
        $validated = $request->validated();

        // Xử lý lưu ảnh
        $validated['image_path'] = $this->handleImageUpload($request);

        // Xác định vị trí position
        $maxPosition = Slider::where('category_id', $validated['category_id'])->max('position');
        $validated['position'] = $maxPosition ? $maxPosition + 1 : 1;

        // Tạo slider mới
        Slider::create($validated);

        return redirect()->route('admin.slider.index', ['category_id' => $validated['category_id']])
            ->with('success', 'Slider đã được tạo thành công!');
    }

    public function edit($id)
    {
        $slider = Slider::findOrFail($id);  // Dùng findOrFail thay cho find

        $categories = Category::whereNull('parent_id')->get();
        return view('admin.slider.edit', compact('slider', 'categories'));
    }

    public function update(UpdateSliderRequest $request, $id)
    {
        $slider = Slider::findOrFail($id);  // Dùng findOrFail thay cho find
        $validated = $request->validated();

        // Xử lý cập nhật ảnh
        $validated['image_path'] = $this->handleImageUpload($request, $slider->image_path);

        // Cập nhật slider
        $slider->update($validated);

        return redirect()->route('admin.slider.index', ['category_id' => $validated['category_id']])
            ->with('success', 'Slider đã được cập nhật thành công!');
    }

    protected function handleImageUpload($request, $existingImagePath = null)
    {
        if ($request->hasFile('image_path')) {
            $imagePath = $request->file('image_path')->store('sliders', 'public');

            // Xóa ảnh cũ nếu tồn tại
            if ($existingImagePath && Storage::disk('public')->exists($existingImagePath)) {
                Storage::disk('public')->delete($existingImagePath);
            }

            return $imagePath;
        }

        return $existingImagePath;
    }

    public function destroy($id)
    {
        // Tìm slider và xóa mềm
        $slider = Slider::findOrFail($id);

        DB::transaction(function () use ($slider) {
            // Tìm giá trị position nhỏ nhất trong các bản ghi đã bị xóa mềm
            $minDeletedPosition = Slider::onlyTrashed()->min('position');

            // Nếu không có bản ghi nào bị xóa, gán position là -1. Ngược lại, giảm giá trị âm nhỏ nhất thêm 1
            $slider->position = $minDeletedPosition ? $minDeletedPosition - 1 : -1;

            // Xóa mềm bản ghi
            $slider->save();
            $slider->delete();

            // Điều chỉnh lại vị trí của các bản ghi chưa bị xóa
            $this->reorderPositions($slider->category_id);
        });

        return redirect()->route('admin.slider.index', ['category_id' => $slider->category_id])
            ->with('success', 'Slider đã được xóa và vị trí đã được cập nhật.');
    }

    protected function reorderPositions($category_id)
    {
        // Lấy tất cả các sliders chưa bị xóa mềm, sắp xếp theo position
        $sliders = Slider::whereNull('deleted_at')
            ->where('category_id', $category_id)
            ->orderBy('position')
            ->get();

        // Cập nhật lại position từ 1 trở đi
        foreach ($sliders as $index => $slider) {
            $slider->position = $index + 1; // Gán lại thứ tự từ 1 trở đi
            $slider->save();
        }
    }

    public function restore($id)
    {
        // Tìm slider đã bị xóa mềm
        $slider = Slider::onlyTrashed()->findOrFail($id);
        $slider->restore();  // Khôi phục slider
        $maxPosition = Slider::where('category_id', $slider->category_id)->max('position');
        $slider->position = $maxPosition ? $maxPosition + 1 : 1;
        $slider->save();
        return redirect()->route('admin.slider.index', ['category_id' => "trash"])
            ->with('success', 'Slider đã được khôi phục!');
    }


    public function forceDelete($id)
    {
        // Tìm slider đã bị xóa mềm và xóa vĩnh viễn
        $slider = Slider::onlyTrashed()->findOrFail($id);
        $slider->forceDelete();  // Xóa vĩnh viễn slider

        return redirect()->route('admin.slider.index', ['category_id' =>'trash'])
            ->with('success', 'Slider đã bị xóa vĩnh viễn!');
    }

}
