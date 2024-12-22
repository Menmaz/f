@php
$title = data_get($entry, $column['title']);

$cover = data_get($entry, $column['cover']);

$latest_chapter_number = data_get($entry, 'lastestChapter.0.chapter_number', 'Chưa có chapter nào');

$statuses = data_get($entry, $column['statuses']);
$status = $statuses->first()->slug ?? 'ongoing';

$types = data_get($entry, $column['types'], []);
$type = $types->first()->slug ?? 'New Chapter';
$updated_at = data_get($entry, $column['updated_at'], 'Không xác định');
if ($updated_at !== 'Không xác định') {
    $updated_at = date('d/m/Y', strtotime($updated_at));
}

$config_show_status = [
    'not_defined' => [
        'class' => 'bg-warning',
        'label' => 'Trạng thái chưa xác định',
    ],
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

<div style="display: flex; width: 260px;" class="border rounded">
@includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')
                <img src="{{ $cover }}" class="img-thumbnail" width="65px">
                <div style="max-width: 182px;  white-space: normal;margin-left:5px">
                    <b class="text pb-2">{{ $title }}</b><br>
                    <b class="text-danger">[Chapter mới nhất: {{$latest_chapter_number}}]</b><br>
                    <span class="badge bg-primary font-weight-normal">{{$type}}</span>
                    <span class="badge bg-info font-weight-normal">{{ $config_show_status[$status]['label'] }}</span>
                    <p style="font-size: 14px;padding: 2px;font-weight: bold;" class="text-success">Cập nhật lúc: {{ $updated_at }}</p>
                </div>
                @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
        </div>

       