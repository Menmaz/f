    <label>{{$label}}</label>
        <textarea name="content" rows="8" placeholder="http://example.com/images1.jpghttp://example.com/images2.jpghttp://example.com/images3.jpg" class="form-control">@foreach($content as $url){{$url}}&#10;@endforeach
        </textarea>