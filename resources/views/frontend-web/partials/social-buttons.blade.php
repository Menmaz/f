<style>
.share-buttons {
    display: flex;
    justify-content: center;
    gap: 10px; /* Space between buttons */
    margin-top: 20px;
}

.btn-facebook, .btn-messenger {
    display: inline-flex;
    align-items: center;
    justify-content: center; /* Center content horizontally */
    background-color: #3b5998; /* Facebook blue */
    color: white;
    padding: 15px;
    border-radius: 5px;
    text-decoration: none;
    transition: background-color 0.3s ease;
    height: 35px; 
    box-sizing: border-box;
}

.btn-facebook i, .btn-messenger i {
    margin-right: 8px; /* Space between icon and text */
}

.btn-facebook:hover {
    background-color: #2d4373; /* Darker Facebook blue */
    color: white;
}

.btn-messenger {
    background-color: #0084ff; /* Messenger blue */
}

.btn-messenger:hover {
    background-color: #006bbf; /* Darker Messenger blue */
    color: white;
}
</style>

<div class="share-buttons">
    <a rel="nofollow" href="http://www.facebook.com/share.php?u=<;url>" 
       onclick="return fbs_click()" target="_blank" 
       class="btn-facebook">
        <i class="fab fa-facebook-f"></i> Chia sẻ
    </a>
    {{-- <a rel="nofollow" href="https://www.facebook.com/dialog/share?link=https%3A%2F%2Fwww.youtube.com&app_id=1234567890" 
       target="_blank" 
       class="btn-messenger">
        <i class="fab fa-facebook-messenger"></i> Chia sẻ
    </a> --}}
</div>

<script type="text/javascript">
    function fbs_click() {
      u=location.href;t=document.title;
      window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent(u)+'&t='+encodeURIComponent(t),'sharer','toolbar=0,status=0,width=626,height=436');
      return false;
    }
    // function mess_click() {
    //   u=location.href;t=document.title;
    //   window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent(u)+'&t='+encodeURIComponent(t),'sharer','toolbar=0,status=0,width=626,height=436');
    //   return false;
    // }
    </script>

