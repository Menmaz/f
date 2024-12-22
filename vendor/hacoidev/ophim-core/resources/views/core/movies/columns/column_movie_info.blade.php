@php
$title = data_get($entry, $column['title']);
$cover = data_get($entry, $column['cover']);
$slug = data_get($entry, $column['slug']);
$chapters_number = count(data_get($entry, $column['chapters']));
$statuses = data_get($entry, $column['statuses']);
$status = $statuses[0]->slug;

$config_show_status = [
    'coming_soon' => [
        'class' => 'bg-warning',
        'label' => 'Sắp ra mắt',
    ],
    'ongoing' => [
        'class' => 'bg-info',
        'label' => 'Đang phát hành',
    ],
    'completed' => [
        'class' => 'bg-success',
        'label' => 'Hoàn thành',
    ],
];
@endphp

<div style="display: flex; width: 250px;" class="border rounded">
@includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')
                <img src="{{ $cover }}" class="img-thumbnail" height="100px" width="68px">
                <div style="max-width: 182px;  white-space: normal;">
                    <span class="text-primary pb-2">{{ $title }}</span><br>
                    <span class="text-muted pb-2">
                        <small>({{$slug}})</small>
                    </span>
                    @if($chapters_number > 0)
                    <span class="text-danger">[Chapter {{$chapters_number}}]</span><br>
                    @else 
                    <span class="text-danger">[Chưa có tập nào]</span><br>
                    @endif
                   
                    
                    <span class="badge bg-info font-weight-normal">{{ $config_show_status[$status]['label'] }}</span>
                </div>
                @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
        </div>
