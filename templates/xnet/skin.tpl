{**************************************************************************}
{*                                                                        *}
{*  Copyright (C) 2003-2008 Polytechnique.org                             *}
{*  http://opensource.polytechnique.org/                                  *}
{*                                                                        *}
{*  This program is free software; you can redistribute it and/or modify  *}
{*  it under the terms of the GNU General Public License as published by  *}
{*  the Free Software Foundation; either version 2 of the License, or     *}
{*  (at your option) any later version.                                   *}
{*                                                                        *}
{*  This program is distributed in the hope that it will be useful,       *}
{*  but WITHOUT ANY WARRANTY; without even the implied warranty of        *}
{*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *}
{*  GNU General Public License for more details.                          *}
{*                                                                        *}
{*  You should have received a copy of the GNU General Public License     *}
{*  along with this program; if not, write to the Free Software           *}
{*  Foundation, Inc.,                                                     *}
{*  59 Temple Place, Suite 330, Boston, MA  02111-1307  USA               *}
{*                                                                        *}
{**************************************************************************}

{include file="skin/common.doctype.tpl"}
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="Les associations polytechniciennes" />
    <meta name="keywords" content="Ecole polytechnique, associations polytechniciennes, groupes X, binets" />

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="images/favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="images/logo-xnet.png" type="image/png" />

    <link rel="stylesheet" type="text/css" href="css/xnet.css" media="screen" />

    <link rel="bookmark" href="http://www.polytechnique.fr/"        title="| École polytechnique" />
    <link rel="bookmark" href="http://www.polytechnique.edu/"       title="| Institutionnal site" />
    <link rel="bookmark" href="http://www.fondationx.org/"          title="| FX" />
    <link rel="bookmark" href="http://www.polytechniciens.com/"     title="| AX" />
    <link rel="bookmark" href="http://www.polytechnique.org/"       title="| Polytechnique.org" />
    <link rel="bookmark" href="http://www.polytechnique.fr/eleves/" title="| Site d'élèves" />

    {foreach from=$pl_css item=css}
    <link rel="stylesheet" type="text/css" href="css/{$css}" />
    {/foreach}
    {foreach from=$pl_inline_css item=css} 
    <style type="text/css"> 
    {$css|smarty:nodefaults} 
    </style> 
    {/foreach} 
    <script type="text/javascript">
    var platal_baseurl = "{$globals->baseurl}/"
    if (window.top != window)
      document.write('<link rel="stylesheet" type="text/css" href="css/onlycontent.css" media="all"/>');
    </script>
    {foreach from=$pl_js item=js}
    <script type="text/javascript" src="javascript/{$js}"></script>
    {/foreach}
    {javascript name=overlib}

    {if $pl_rss}
    <link rel="alternate" type="application/rss+xml" title="{$pl_rss.title}" href="{$pl_rss.href}" />
    {/if}

    {if $pl_extra_header}
    {$pl_extra_header|smarty:nodefaults}
    {/if}

    <title>Les associations polytechniciennes</title>
    {include file=skin/common.bandeau.head.tpl}
  </head>
  <body>
    {include file=skin/common.devel.tpl}
    {if !$simple}
      {include file=skin/common.bandeau.tpl}
    {/if}

    <table id="layout" cellspacing="0" cellpadding="0">
    {if !$simple}
      <tr>
        <td colspan="2">
        <table cellspacing="0" cellpadding="0" id="top">
          <tr>
            <td style="width: 150px">
              <a href="{if $is_logged}login{/if}"><img src="images/asso-montants.png" alt="Logo Assos" /></a>
            </td>
            <td style="width: 106px">
              {if $xnet_type}
              <img src="images/logo_{$xnet_type}.png" alt="Logo {$xnet_type}" width="106" height="96" />
              {else}
              <img src="images/logo_institutions.png" alt="Logo {$xnet_type}" width="106" height="96" />
              {/if}
            </td>
            <td style="width: 44px">
              <img src="images/fin_logo.jpg" alt="Fin logo" width="44" height="96" />
            </td>
            <td style="width: auto;">
              <img src="images/bandeau.jpg" alt="bandeau" height="96" width="100%" />
            </td>
            {if $xnet_type}
            <td style="width: 280px">
              <a href="{if $xnet_type eq plan}plan{else}groups/{$xnet_type}{/if}"><img src="images/texte_{$xnet_type}.jpg" alt="{$xnet_type}" width="280" height="96" /></a>
            </td>
            {if $asso}
            <td class="logo">
              {if $asso.site}
                <a href="{$asso.site}"><img src='{$platal->ns}logo' alt="LOGO" height="80" /></a>
              {else}
                <img src='{$platal->ns}logo' alt="LOGO" height="80"/>
              {/if}
            </td>
            {/if}
            {else}
            <td class="logo">
              <img src="images/asso.png" alt="Le serveur des activés associative des X" />
              <a href="http://www.polytechnique.org">
                <img src="images/logo-xorg.png" alt="Polytechnique.org" height="80" />
              </a>
            </td>
            {/if}
          </tr>
        </table>
        </td>
      </tr>
    {/if}{* fin simple *}

      {if $menu && !$simple}
      <tr>
        <td id="menu">
          {foreach from=$menu key=title item=submenu}
            {if $title neq 'no_title'}<h1>{$title}</h1>{/if}
            {foreach from=$submenu key=tit item=url}
              {if is_array($url)}
                <a{foreach from=$url key=var item=val} {$var}="{$val}"{/foreach}>{$tit}</a>
              {else}
                <a href="{$url}">{$tit}</a>
              {/if}
            {/foreach}
          {/foreach}
          {if $asso && ($is_admin ||
                      ($smarty.session.suid && ($smarty.session.suid.perms->hasFlag('admin') ||
                                                $smarty.session.suid.may_update[$asso.id])))}
          <h1>Voir le site comme...</h1>
          <form method="post" action="{$platal->ns}change_rights">
            <div>
              <select name="right" onchange="this.form.submit()" style="margin: 0; padding: 0">
                {if hasPerm('admin') || ($smarty.session.suid && $smarty.session.suid.perms->hasFlag('admin'))}
                <option value="admin" {if hasPerm('admin')}selected="selected"{/if}>Administrateur</option>
                {/if}
                <option value="anim" {if $is_admin && !hasPerm('admin')}selected="selected"{/if}>Animateur</option>
                <option value="member" {if !$is_admin && $is_member}selected="selected"{/if}>Membre</option>
                <option value="logged" {if !$is_admin && !$is_member}selected="selected"{/if}>Non-membre</option>
              </select>
            </div>
          </form>
          {/if}
        </td>
        <td id="body">
          {include file="skin/common.content.tpl"}
        </td>
      </tr>
      {else}
      <tr>
        <td colspan="2">
          {include file="skin/common.content.tpl"}
        </td>
      </tr>
      {if !$simple}
      <tr class="hideable"><td colspan="2"><img src="images/barre.png" alt="----------" width="100%" /></td></tr>

      <tr class="hideable">
        <td colspan="2">
          <table class="links" summary="liens" cellspacing="0" cellpadding="0">
            <tr>
              <td class="left">   <a href="groups/groupesx">Groupes X</a> </td>
              <td class="left"> <a href="groups/binets">Binets</a> </td>
              <td class="center"> <a href="groups/promotions">Promotions</a> </td>
              <td class="center"> <a href="groups/institutions">Institutions</a> </td>
              <td class="right">  <a href="plan">Tous</a> </td>
            </tr>
          </table>
        </td>
      </tr>
      {/if}
      {/if}
    {if !$simple}
      <tr class="hideable"><td colspan="2"><img src="images/barre.png" alt="----------" width="100%" /></td></tr>

      <tr class="hideable">
        <td colspan="2">
        <table style="width: 100%">
          <tr>
            <td id="perso">
              {list_all_my_groups}
              {if !$smarty.session.auth}
                <div>Me connecter&nbsp;:</div>
                <a class='gp' href="login/{if $platal->pl_self() eq 'exit'}index{else}{$platal->pl_self()}{/if}">polytechnicien</a>
                {if $platal->pl_self() neq 'exit'}
                  <!-- <script type="text/javascript" src="login/autologin/{$platal->pl_self()}?forceXml=1"></script> -->
                {/if}
              {/if}
            </td>
            <td class="right" style="vertical-align: middle">
              {if hasPerm('admin')}
              <a href="admin" title="Administration des groupes">
                Gérer les groupes
                {icon name=wrench title="Administration"}
              </a><br />
              {/if}
              <a href="Xnet" title="Manuel d'aide en ligne">
                Manuel de l'utilisateur
                {icon name=lightbulb title="Documentation"}
              </a>
            </td>
          </tr>
        </table>
        </td>
      </tr>

      <tr class="hideable"><td colspan="2"><img src="images/barre.png" alt="----------" width="100%" /></td></tr>

      <tr class="hideable">
        <td colspan="2" id="credits">
          <a href="plan">plan du site</a> -
          <a href="Xnet/Services">services proposés</a> -
          <a href="Xnet/APropos">à propos de ce site</a> -
          <a href="mailto:contact@polytechnique.org">nous contacter</a>
          {if $smarty.session.auth}
            - <a href="send_bug/{ $smarty.server.REQUEST_URI }" class="popup_840x600">signaler un bug</a>
          {/if}
          <br />
          Plat/al {#globals.version#} - © Copyright 2000-2008 <a href="http://x-org.polytechnique.org/">Association Polytechnique.org</a>
        </td>
      </tr>
    {/if}
    </table>
 </body>
</html>
{* vim:set et sw=2 sts=2 sws=2 enc=utf-8: *}