<?php
/** Rich text editor (Quill) bound to a hidden <textarea name="content">. */
function editor_assets(): string
{
    return '<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">';
}

function editor_field(string $name, ?string $html): string
{
    return '<div id="editor" class="bg-white">' . $html . '</div><textarea name="' . e($name) . '" id="editorInput" class="d-none">' . e($html) . '</textarea>'
        . '<div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="htmlMode"><label class="form-check-label small" for="htmlMode">Edit raw HTML</label></div>';
}

function editor_script(): string
{
    return <<<'HTML'
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
(function(){
  const q = new Quill('#editor', {theme: 'snow', modules: {toolbar: [[{header: [2, 3, 4, false]}], ['bold', 'italic', 'underline', 'blockquote'], [{list: 'ordered'}, {list: 'bullet'}], ['link', 'image', 'video'], [{align: []}], ['clean']]}});
  const input = document.getElementById('editorInput'), toggle = document.getElementById('htmlMode'), box = document.getElementById('editor');
  toggle.addEventListener('change', function(){
    if (this.checked) { input.value = q.root.innerHTML; input.classList.remove('d-none'); input.classList.add('form-control'); input.rows = 16; box.parentNode.querySelector('.ql-toolbar').classList.add('d-none'); box.classList.add('d-none'); }
    else { q.root.innerHTML = input.value; input.classList.add('d-none'); box.parentNode.querySelector('.ql-toolbar').classList.remove('d-none'); box.classList.remove('d-none'); }
  });
  input.form.addEventListener('submit', function(){ if (!toggle.checked) input.value = q.root.innerHTML; });
})();
</script>
HTML;
}
