<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Numerology Application</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
body{padding:20px;background:#f0f2f5;}
.result-badge{font-size:1.3rem;transition:all 0.3s ease;}
#messageArea .alert{animation:fadeIn 0.5s;}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.delete-name, .restore-name{transition:transform 0.2s;}
.delete-name:hover, .restore-name:hover{transform:scale(1.1);}
</style>
</head>
<body>
<div class="container">
  <h1 class="mb-4 text-primary">✨ Numerology Application</h1>
  <div class="row">
    <div class="col-md-6">
      <form id="nameForm" class="mb-3">
        <div class="mb-3">
          <label class="form-label">Enter Name</label>
          <input type="text" class="form-control" id="nameInput" placeholder="e.g., John Doe" autocomplete="off" spellcheck="false" required>
          <div class="form-text text-muted">Only alphabetic characters allowed.</div>
        </div>
        <div class="mb-2">
          <strong>Computed Number:</strong>
          <span id="computedNumber" class="badge bg-primary result-badge">—</span>
          <button type="button" id="showAdvantagesBtn" class="btn btn-outline-secondary btn-sm ms-2" disabled>Show Advantages</button>
        </div>
        <div class="mb-3">
          <button type="submit" class="btn btn-success">Save Name</button>
          <button type="button" id="clearBtn" class="btn btn-secondary">Clear</button>
        </div>
      </form>
      <div id="messageArea"></div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label">Filter by number</label>
        <select id="filterNumber" class="form-select">
          <option value="">All</option>
          <option>1</option><option>2</option><option>3</option><option>4</option>
          <option>5</option><option>6</option><option>7</option><option>8</option>
          <option>9</option><option>11</option><option>22</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Search names</label>
        <input type="text" id="searchBox" class="form-control" placeholder="Type to search...">
      </div>
    </div>
  </div>

  <hr>
  <h3>Entered Names</h3>
  <table id="namesTable" class="display table table-striped table-hover" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Original Name</th>
          <th>Normalized_name</th>
        <th>Number</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Advantages Modal -->
<div class="modal fade" id="advantagesModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-primary">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Advantages for number <span id="advNumTitle"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="advBody"></div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
// Alphabet mapping
const letterMap = {
  a:1, b:2, c:3, d:4, e:5, f:8, g:3, h:5, i:1,
  j:1, k:2, l:3, m:4, n:5, o:7, p:8, q:1, r:2,
  s:3, t:4, u:6, v:6, w:6, x:5, y:1, z:7
};

// Validation
function normalizeName(s){s=s.toLowerCase().trim();s=s.replace(/[^a-z\s]/g,'');s=s.replace(/\s+/g,' ');return s;}
function computeNumberFromName(s){
  s=normalizeName(s); if(!s.length)return null;
  let sum=0;
  for(let ch of s){if(ch!==' ')sum+=(letterMap[ch]||0);}
  while(sum>9){if(sum===11||sum===22)break; let tmp=0; for(let d of String(sum)) tmp+=Number(d); sum=tmp;}
  return String(sum);
}

// DataTable
let table;
$(document).ready(function(){
  table=$('#namesTable').DataTable({
    ajax:{url:'api.php?action=list',dataSrc:r=>r.data||[]},
    columns:[
      {data:'id'},
      {data:'original_name'},
      {data:'normalized_name'},
      {data:'computed_number'},
      {data:'created_at'},
      {data:null,render:(d,t,row)=>`
        <button class="btn btn-sm btn-outline-info show-adv" data-num="${row.computed_number}">Advantages</button>
        <button class="btn btn-sm btn-outline-danger delete-name ms-1" data-id="${row.id}">Delete</button>
      `}
    ],
    order:[[3,'desc']],
    pageLength:10
  });

  // Search & Filter
  $('#searchBox').on('input',()=>table.search($('#searchBox').val()).draw());
  $('#filterNumber').on('change',()=>table.ajax.url($('#filterNumber').val()?`api.php?action=list&number=${encodeURIComponent($('#filterNumber').val())}`:'api.php?action=list').load());

  // Keyup input calculation with exception handling
  $('#nameInput').on('input keyup',function(){
    try{
      const val=$(this).val();
      const num=computeNumberFromName(val);
      if(num){$('#computedNumber').text(num).removeClass('bg-danger').addClass('bg-primary');$('#showAdvantagesBtn').prop('disabled',false).data('num',num);}
      else{$('#computedNumber').text('—').removeClass('bg-primary').addClass('bg-danger');$('#showAdvantagesBtn').prop('disabled',true).data('num','');}
    }catch(e){console.error(e);$('#computedNumber').text('Error').removeClass('bg-primary').addClass('bg-danger');}
  });

  $('#clearBtn').click(function(){
    $('#nameInput').val('');
    $('#computedNumber').text('—').removeClass('bg-danger').addClass('bg-primary');
    $('#showAdvantagesBtn').prop('disabled',true);
    $('#messageArea').html('');
  });

  // Form submit
  $('#nameForm').on('submit',function(e){
    e.preventDefault();
    const name=$('#nameInput').val().trim();
    if(!name){showMessage('warning','Please enter a valid name.');return;}
    try{
      const form=new FormData(); form.append('action','add'); form.append('name',name);
      fetch('api.php?action=add',{method:'POST',body:form})
        .then(r=>r.json())
        .then(handleAddResponse)
        .catch(err=>showMessage('danger','Error: '+err.message));
    }catch(err){showMessage('danger','Unexpected error: '+err.message);}
  });

  // Advantages modal
  $(document).on('click','.show-adv',function(){showAdvantages($(this).data('num'));});
  $('#showAdvantagesBtn').click(function(){showAdvantages($(this).data('num'));});

  // Soft delete
  $(document).on('click','.delete-name',function(){
    const id=$(this).data('id');
    if(!confirm('Are you sure you want to delete this record?'))return;
    const form=new FormData();form.append('action','delete');form.append('id',id);
    fetch('api.php?action=delete',{method:'POST',body:form})
      .then(r=>r.json())
      .then(json=>{if(json.status==='ok'){showMessage('info','Record deleted successfully.');table.ajax.reload(null,false);}else showMessage('danger',json.message||'Delete failed');})
      .catch(err=>showMessage('danger','Error: '+err.message));
  });
});

// Response handlers
function handleAddResponse(resp){
  if(resp.status==='ok'){
    showMessage('success',`Saved successfully (ID: ${resp.record.id})`);
    $('#nameInput').val(''); $('#computedNumber').text('—').removeClass('bg-danger').addClass('bg-primary'); $('#showAdvantagesBtn').prop('disabled',true);
    table.ajax.reload(null,false);
  } else if(resp.status==='exists'){
    showMessage('warning','Name already exists. Showing existing record.');
    const rec=resp.record; table.ajax.reload(()=>table.search(rec.original_name).draw(),false);
    showAdvantages(rec.computed_number);
  } else showMessage('danger',resp.message||'Unknown error');
}

// Show advantages modal
function showAdvantages(num){
  if(!num)return;
  fetch(`api.php?action=advantages&number=${encodeURIComponent(num)}`)
    .then(r=>r.json())
    .then(json=>{
      if(json.status!=='ok'){showMessage('danger','Could not fetch advantages'); return;}
      const items=json.data;
      $('#advNumTitle').text(num);
      $('#advBody').html(items.length?items.map(it=>`<div class="mb-3"><h5>${escapeHtml(it.title)}</h5><p>${escapeHtml(it.description)}</p></div>`):'<p>No advantages recorded for this number.</p>');
      new bootstrap.Modal(document.getElementById('advantagesModal')).show();
    }).catch(e=>showMessage('danger','Error fetching advantages'));
}

// User-friendly messages
function showMessage(type,text){
  $('#messageArea').html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${escapeHtml(text)}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
}

// Escape HTML
function escapeHtml(s){if(!s)return'';return s.replace(/[&<>"'`=\/]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#47;','`':'&#96;','=':'&#61;'})[c]);}
</script>
</body>
</html>
