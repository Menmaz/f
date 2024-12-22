@php 
$weekdays = [ 
    'hang-ngay' => "Hằng ngày", 
    'chu-nhat' => "Chủ nhật",
    'thu-hai' => "Thứ hai",
    'thu-ba' => "Thứ ba",
    'thu-tu' => "Thứ tư",
    'thu-nam' => "Thứ năm",
    'thu-sau' => "Thứ sáu",
    'thu-bay' => "Thứ bảy", 
    ]; 
    @endphp 

    <style>
    .weekday-buttons {
      display: flex;
      justify-content: space-around;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
    }

    .weekday-buttons .btn {
      flex: 1;
      max-width: 150px text-align: center;
      padding: 7px 10px;
      transition: background-color 0.3s;
      border: 2px solid #2f70a0
    }

    .weekday-buttons .btn:hover {
      box-shadow: 0px 8px 20px #2f70a0
    }
  </style>
  <center class="weekday-buttons"> 
    @foreach($weekdays as $key => $label) 
    <a href="{{ route('mangas.scheduled', ['weekday' => $key ]) }}" class="btn btn-secondary2 @if(request()->input('weekday') === $key) disabled @else active @endif">
      <span>{{ $label }}</span>
    </a>
     @endforeach 
    </center>