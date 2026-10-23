{{-- Bỏ điểm danh (bấm nhầm "Các bé còn lại đều có mặt" vào ngày nghỉ lễ...) → về "chưa điểm danh". Với bé gửi ngày, ngày bị bỏ sẽ không tính tiền. --}}
<form method="POST" action="{{ route('attendance.destroy', $row->id) }}" class="ml-auto shrink-0"
      onsubmit="return confirm({{ \Illuminate\Support\Js::from('Bỏ điểm danh ngày '.$row->date->format('d/m').' của bé này (về “chưa điểm danh”)?') }})">
    @csrf @method('DELETE')
    <button class="whitespace-nowrap text-xs px-2 py-1.5 rounded-lg text-neutral-400 hover:text-red-600 hover:bg-red-50 font-medium"><i class="fa-solid fa-rotate-left mr-1"></i>Bỏ điểm danh</button>
</form>
