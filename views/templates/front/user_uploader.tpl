{**
    * 2007-2020 PrestaShop and Contributors
    *
    * NOTICE OF LICENSE
    *
    * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
    * that is bundled with this package in the file LICENSE.txt.
    * It is also available through the world-wide-web at this URL:
    * https://opensource.org/licenses/AFL-3.0
    * If you did not receive a copy of the license and are unable to
    * obtain it through the world-wide-web, please send an email
    * to license@prestashop.com so we can send you a copy immediately.
    *
    * @author    PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2020 PrestaShop SA and Contributors
    * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
   {extends file='customer/page.tpl'}
   
   {block name='page_title'}
       {l s='Document de vérification' mod='snt_doc_checker'}
   {/block}
   
   
   {block name='page_content'}
   
       <div class="row">
           <div class="col-12">
               <!-- Notification area -->
               {if isset($smarty.get.error) && $smarty.get.error == '0'}
                <div class="alert alert-success file-comment-container mb-4" role="alert" id="success-alert">
                    <span id="file-comment">{l s='Le document a été envoyé avec succès.' mod='snt_doc_checker'}</span>
                </div>
                <script>
                    $(document).ready(function(){
                        setTimeout(function() {
                            $("#success-alert").fadeOut(500);
                        }, 5000);
                    });
                </script>
               {elseif isset($smarty.get.error) && $smarty.get.error == '1'}
                <div class="alert alert-danger file-comment-container mb-4" role="alert" id="error-alert">
                    <span id="file-comment">{l s='Une erreur est survenue lors de l\'envoi du document.' mod='snt_doc_checker'}</span>
                </div>
                <script>
                    $(document).ready(function(){
                        setTimeout(function() {
                            $("#error-alert").fadeOut(500);
                        }, 5000);
                    });
                </script>
               {/if}
   
               {hook h=displayConfirmPage}
               <div class="card mb-4">
                   <div class="card-body">
                       <h5 class="card-title">{l s='Vérification des documents' d='Shop.Theme.Customeraccount'}</h5>
                       <p class="card-text">
                           L'achat de produits réglementés de catégorie C & D sur notre site <strong>nécessite certaines vérifications</strong>. 
                           Sur cette page, vous pouvez envoyer vos pièces justificatives ainsi que consulter l'avancement de vos derniers envois. 
                           Il est important de savoir qu'une pièce justificative de type permis de chasser, validation annuelle ou licence de tir doit être renouvelée chaque année.
                       </p>
                   </div>
               </div>
           </div>
       </div>
   
       {if isset($pieces) && $pieces}
       <div class="row mb-4">
           <div class="col-12">
               <div class="shadow-sm border-0 rounded-lg">
                   <div class="card-header bg-primary text-white">
                       <h2 class="h4 mb-0 text-white">{l s='Récapitulatif de vos documents' d='Shop.Theme.Customeraccount'}</h2>
                   </div>
                   <div class="card-body">
                       <div class="docs-container table-responsive">
                           <table id="tableRecap" class="table table-bordered shadow-sm">
                               <thead>
                                   <tr class="bg-light">
                                       <th class="py-3 text-secondary font-weight-bold d-none d-md-table-cell">Référence</th>
                                       <th class="py-3 text-secondary font-weight-bold">Type de document</th>
                                       <th class="py-3 text-secondary font-weight-bold">État</th>
                                   </tr>
                               </thead>    
                               <tbody>
                                   {foreach from=$pieces key=k item=v }
                                   <tr class="tr-docline">
                                       <td class="align-middle font-weight-bold d-none d-md-table-cell">#{$k}</td>
                                       <td class="align-middle">{$v.type} <span class="badge badge-info">transmis le : {$v.date|date_format:"%d/%m/%Y à %H:%M"}</span></td>
                                       <td class="align-middle">
                                           {if $v.statut == 'valide'}
                                               <span class="badge badge-success px-3 py-2">Valide</span>
                                           {elseif $v.statut == 'refuse' || $v.statut == 'expire'}
                                               <span class="badge badge-danger px-3 py-2">
                                                   {if $v.statut == 'refuse'}Refusé{else}Expiré{/if}
                                               </span>
                                           {else}
                                               <span class="badge badge-warning px-3 py-2">{$v.statut}</span>
                                           {/if}
                                       </td>
                                   </tr>
                                   {/foreach}
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>
           </div>
       </div>
       {/if}
       <div class="row">
           <div class="col-12">
               <div class="card shadow-sm border-0 rounded-lg">
                   <div class="card-header bg-primary text-white py-3">
                       <h2 class="h4 mb-0 font-weight-bold text-white">{l s='Envoi d\'un document' d='Shop.Theme.Customeraccount'}</h2>
                   </div>
                   <div class="card-body p-4">
                       <p class="card-text">
                           Pour envoyer vos pièces, vous pouvez passer par le formulaire suivant. Merci d'envoyer des pièces aux formats 
                           <span class="badge badge-info">png</span>
                           <span class="badge badge-info">jpeg</span>
                           <span class="badge badge-info">jpg</span>
                           <span class="badge badge-info">pdf</span>.
                           La taille maximale par fichier est de <strong>5MB</strong>.
                       </p>
                       <p class="card-text">
                           Il est possible d'envoyer un document en une fois, en utilisant le bouton (recto-verso) ou les deux côtés séparément 
                           en utilisant le bouton recto puis verso. Il est nécessaire d'accepter les conditions avant de soumettre vos documents.
                       </p>
                       
                       <form action="" method="POST" id="form-upload" enctype="multipart/form-data" class="mt-4">
                           <div class="card mb-4 shadow-sm">
                               <div class="card-header bg-light">
                                   <h3 class="h5 mb-0 font-weight-bold">Type de document</h3>
                               </div>
                               <div class="card-body">
                                   <div class="form-group">
                                       <label for="type-selector" class="form-label">Veuillez choisir le type de document à envoyer :</label>
                                       <select name="doc-select" id="type-selector" class="form-control form-control-lg" required="required">
                                           <option value="">Sélectionnez un type de document</option>
                                           <option value="pi">Carte Nationale d'Identité</option>
                                           <option value="pc">Permis de chasse</option>
                                           <option value="vpc">Validation du permis de chasse</option>
                                           <option value="lfft">Licence de la Fédération Française de Tir</option>
                                           <option value="lffb">Licence de la Fédération Française de Biathlon</option>
                                           <option value="lffbt">Licence de la Fédération Française de ball-trap</option>
                                           <option value="cc">Carte de collectionneur</option>
                                           <option value="pp">Passeport</option>
                                           <option value="pcd">Permis de conduire</option>
                                       </select>
                                   </div>
                               </div>
                           </div>
                           
                           <div class="card mb-4 shadow-sm">
                               <div class="card-header bg-light">
                                   <h3 class="h5 mb-0 font-weight-bold">Fichiers</h3>
                               </div>
                               <div class="card-body">
                                   <div class="form-group">
                                       <label for="file-input-1" class="form-label">Veuillez choisir votre fichier (recto ou recto verso) :</label>
                                       <div class="input-group mb-3">
                                           <div class="input-group-prepend">
                                               <button type="button" class="px-4 btn btn-primary" onclick="document.querySelector('#file-input-1').click()" id="btn-input-1">
                                                   <i class="material-icons">add</i>
                                               </button>
                                           </div>
                                           <input type="file" name="file_url" id="file-input-1" class="d-none" onchange="changelbl1()" required="required" />
                                           <div class="form-control" id="lbl-input-1">Ajouter une pièce</div>
                                       </div>
                        
                                       <small class="form-text text-muted">Formats acceptés: .jpg, .jpeg, .png, .pdf (max: 5MB)</small>
                                   </div>
                                   
                                   <div class="form-group mt-4">
                                       <label for="file-input-2" class="form-label">Veuillez choisir votre fichier (facultatif verso) :</label>
                                       <div class="input-group mb-3">
                                           <div class="input-group-prepend">
                                               <button type="button" class="px-4 btn btn-primary" onclick="document.querySelector('#file-input-2').click()" id="btn-input-2">
                                                   <i class="material-icons">add</i>
                                               </button>
                                           </div>
                                           <input type="file" name="file_url2" id="file-input-2" class="d-none" onchange="changelbl2()" />
                                           <div class="form-control" id="lbl-input-2">Ajouter une pièce</div>
                                       </div>

                                       <small class="form-text text-muted">Formats acceptés: .jpg, .jpeg, .png, .pdf (max: 5MB)</small>
                                   </div>
                               </div>
                           </div>
                           
                           <div class="card mb-4 shadow-sm">
                               <div class="card-header bg-light">
                                   <h3 class="h5 mb-0 font-weight-bold">Mentions légales</h3>
                               </div>
                               <div class="card-body">
                                   <div class="form-check">
                                       <input type="checkbox" class="form-check-input" id="scales" name="scales" required>
                                       <label class="form-check-label" for="scales">
                                           J'accepte les <a href="{$linkMention}" target="_blank">conditions</a>.
                                       </label>
                                   </div>
                                   <input type="hidden" name="form_send" value="1">
                               </div>
                           </div>
                           
                           <div class="text-center">
                               <button type="submit" name="form_submit" id="btnSubmit" class="btn btn-lg btn-primary shadow-sm" value="Envoyer">
                                   <i class="material-icons">send</i> Envoyer les documents
                               </button>
                           </div>
                       </form>
                   </div>
               </div>
           </div>
       </div>

       
       <script>
           // Enhance file input 1
           function changelbl1() {
               const fileInput = document.getElementById('file-input-1');
               const label = document.getElementById('lbl-input-1');
               
               if (fileInput.files && fileInput.files[0]) {
                   const fileName = fileInput.files[0].name;
                   label.textContent = fileName;
                   label.classList.add('file-selected');
                   
                   // Visual feedback
                   document.getElementById('btn-input-1').classList.add('btn-success');
                   document.getElementById('btn-input-1').classList.remove('btn-primary');
                   
                   // Change icon
                   const icon = document.querySelector('#btn-input-1 .material-icons');
                   icon.textContent = 'check';
                   
                   // Animate briefly
                   label.classList.add('uploading');
                   setTimeout(() => {
                       label.classList.remove('uploading');
                   }, 1500);
               } else {
                   resetFileInput1();
               }
           }
           
           // Enhance file input 2
           function changelbl2() {
               const fileInput = document.getElementById('file-input-2');
               const label = document.getElementById('lbl-input-2');
               
               if (fileInput.files && fileInput.files[0]) {
                   const fileName = fileInput.files[0].name;
                   label.textContent = fileName;
                   label.classList.add('file-selected');
                   
                   // Visual feedback
                   document.getElementById('btn-input-2').classList.add('btn-success');
                   document.getElementById('btn-input-2').classList.remove('btn-primary');
                   
                   // Change icon
                   const icon = document.querySelector('#btn-input-2 .material-icons');
                   icon.textContent = 'check';
                   
                   // Animate briefly
                   label.classList.add('uploading');
                   setTimeout(() => {
                       label.classList.remove('uploading');
                   }, 1500);
               } else {
                   resetFileInput2();
               }
           }
           
           // Reset file input 1
           function resetFileInput1() {
               const fileInput = document.getElementById('file-input-1');
               const label = document.getElementById('lbl-input-1');
               
               fileInput.value = '';
               label.textContent = 'Ajouter une pièce';
               label.classList.remove('file-selected');
               
               document.getElementById('btn-input-1').classList.remove('btn-success');
               document.getElementById('btn-input-1').classList.add('btn-primary');
               
               const icon = document.querySelector('#btn-input-1 .material-icons');
               icon.textContent = 'add';
           }
           
           // Reset file input 2
           function resetFileInput2() {
               const fileInput = document.getElementById('file-input-2');
               const label = document.getElementById('lbl-input-2');
               
               fileInput.value = '';
               label.textContent = 'Ajouter une pièce (optionnel)';
               label.classList.remove('file-selected');
               
               document.getElementById('btn-input-2').classList.remove('btn-success');
               document.getElementById('btn-input-2').classList.add('btn-primary');
               
               const icon = document.querySelector('#btn-input-2 .material-icons');
               icon.textContent = 'add';
           }
           
           // Form validation enhancements
           document.addEventListener('DOMContentLoaded', function() {
               const form = document.getElementById('form-upload');
               
               if (form) {
                   form.addEventListener('submit', function(e) {
                       const fileInput1 = document.getElementById('file-input-1');
                       const typeSelector = document.getElementById('type-selector');
                       
                       // Animate button on submit
                       const submitBtn = document.getElementById('btnSubmit');
                       if (submitBtn) {
                           submitBtn.classList.add('uploading');
                       }
                       
                       // Validate required fields more elegantly
                       if (!fileInput1.files || fileInput1.files.length === 0) {
                           e.preventDefault();
                           document.getElementById('lbl-input-1').classList.add('is-invalid');
                           alert('Veuillez sélectionner un fichier');
                           return false;
                       }
                       
                       if (!typeSelector.value) {
                           e.preventDefault();
                           typeSelector.classList.add('is-invalid');
                           alert('Veuillez sélectionner un type de document');
                           return false;
                       }
                       
                       return true;
                   });
                   
                   // Remove validation warnings on change
                   const typeSelector = document.getElementById('type-selector');
                   if (typeSelector) {
                       typeSelector.addEventListener('change', function() {
                           this.classList.remove('is-invalid');
                       });
                   }
               }
           });
       </script>
   {/block}