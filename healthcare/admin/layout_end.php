</div><!-- /content-wrap -->
</div><!-- /adminMain -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSb(){
  document.getElementById('adminSidebar').classList.toggle('open');
  document.getElementById('sbOverlay').classList.toggle('show');
}
function closeSb(){
  document.getElementById('adminSidebar').classList.remove('open');
  document.getElementById('sbOverlay').classList.remove('show');
}
// Auto hide success alerts
setTimeout(()=>{
  document.querySelectorAll('.alert-success').forEach(el=>{
    el.style.transition='opacity .5s';
    el.style.opacity='0';
    setTimeout(()=>el.remove(),500);
  });
}, 3000);
</script>
</body>
</html>
