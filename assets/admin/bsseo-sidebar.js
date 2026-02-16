/**
 * BSseo – Sidebar-Panel im Block-Editor (Gutenberg)
 * Zeigt dieselben Felder wie die Metabox; Post-Meta über core/editor.
 *
 * @package BSseo
 */

(function () {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var registerPlugin = wp.plugins.registerPlugin;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var SelectControl = wp.components.SelectControl;
	var MediaUpload = wp.blockEditor.MediaUpload;

	var i18n = (typeof window.bsseoSidebar !== 'undefined' && window.bsseoSidebar.i18n) ? window.bsseoSidebar.i18n : {};

	function getLabel(key, fallback) {
		return (i18n[key] && i18n[key].label) ? i18n[key].label : (fallback || key);
	}

	function BSseoSidebarContent() {
		var meta = useSelect(function (select) {
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);

		var editPost = useDispatch('core/editor').editPost;

		function setMeta(attrs) {
			editPost({ meta: Object.assign({}, meta, attrs) });
		}

		var title = (meta._bsseo_title !== undefined) ? meta._bsseo_title : '';
		var description = (meta._bsseo_description !== undefined) ? meta._bsseo_description : '';
		var focusKeyword = (meta._bsseo_focus_keyword !== undefined) ? meta._bsseo_focus_keyword : '';
		var canonical = (meta._bsseo_canonical !== undefined) ? meta._bsseo_canonical : '';
		var noindex = meta._bsseo_noindex === 1 || meta._bsseo_noindex === '1';
		var nofollow = meta._bsseo_nofollow === 1 || meta._bsseo_nofollow === '1';
		var schemaType = (meta._bsseo_schema_type !== undefined) ? meta._bsseo_schema_type : '';
		var ogImageId = meta._bsseo_og_image_id ? parseInt(meta._bsseo_og_image_id, 10) : 0;
		var sources = Array.isArray(meta._bsseo_sources) ? meta._bsseo_sources : [];

		var schemaOptions = [
			{ value: '', label: (i18n.autoSchema || '— Automatisch —') },
			{ value: 'Article', label: 'Article' },
			{ value: 'BlogPosting', label: 'BlogPosting' },
			{ value: 'WebPage', label: 'WebPage' },
			{ value: 'FAQPage', label: 'FAQPage' },
			{ value: 'HowTo', label: 'HowTo' },
		];

		return el(Fragment, {},
			el(TextControl, {
				label: getLabel('title', 'Titel in Suchmaschinen'),
				value: title,
				onChange: function (val) { setMeta({ _bsseo_title: val || '' }); },
				help: (i18n.title && i18n.title.tooltip) ? i18n.title.tooltip : null,
			}),
			el(TextareaControl, {
				label: getLabel('description', 'Kurzbeschreibung für Google'),
				value: description,
				onChange: function (val) { setMeta({ _bsseo_description: val || '' }); },
				rows: 3,
				help: (i18n.description && i18n.description.tooltip) ? i18n.description.tooltip : null,
			}),
			el(TextControl, {
				label: getLabel('focus_keyword', 'Hauptsuchbegriff'),
				value: focusKeyword,
				onChange: function (val) { setMeta({ _bsseo_focus_keyword: val || '' }); },
			}),
			el(TextControl, {
				label: getLabel('canonical', 'Offizielle Adresse dieser Seite'),
				value: canonical,
				onChange: function (val) { setMeta({ _bsseo_canonical: val || '' }); },
				placeholder: 'https://',
			}),
			el(CheckboxControl, {
				label: getLabel('noindex', 'Nicht in Google anzeigen'),
				checked: noindex,
				onChange: function (val) { setMeta({ _bsseo_noindex: val ? 1 : 0 }); },
			}),
			el(CheckboxControl, {
				label: getLabel('nofollow', 'Links nicht bewerten'),
				checked: nofollow,
				onChange: function (val) { setMeta({ _bsseo_nofollow: val ? 1 : 0 }); },
			}),
			el(SelectControl, {
				label: getLabel('schema_type', 'Art der Seite (für Suchmaschinen)'),
				value: schemaType,
				options: schemaOptions,
				onChange: function (val) { setMeta({ _bsseo_schema_type: val || '' }); },
			}),
			el(MediaUpload, {
				allowedTypes: ['image'],
				value: ogImageId,
				onSelect: function (media) { setMeta({ _bsseo_og_image_id: media.id }); },
				render: function (obj) {
					return el('div', { className: 'bsseo-sidebar-og' },
						el('span', { className: 'bsseo-sidebar-og-label' }, getLabel('og_image', 'Vorschaubild in Sozialen Medien')),
						el(wp.components.Button, {
							isSecondary: true,
							onClick: obj.open,
							style: { marginRight: '8px' },
						}, ogImageId ? (i18n.changeImage || 'Bild ändern') : (i18n.selectImage || 'Bild wählen')),
						ogImageId ? el(wp.components.Button, {
							isDestructive: true,
							isLink: true,
							onClick: function () { setMeta({ _bsseo_og_image_id: 0 }); },
						}, i18n.removeImage || 'Entfernen') : null
					);
				},
			}),
			el(BSseoSourcesEditor, {
				sources: sources,
				onChange: function (next) { setMeta({ _bsseo_sources: next }); },
				label: getLabel('sources', 'Quellenangaben'),
			})
		);
	}

	// Einfacher Quellen-Editor: bis zu 5 Zeilen Titel + URL
	function BSseoSourcesEditor(props) {
		var sources = props.sources && props.sources.length ? props.sources : [];
		while (sources.length < 2) {
			sources.push({ title: '', url: '' });
		}
		var maxRows = 5;
		var rows = sources.slice(0, maxRows);

		function updateRow(index, field, value) {
			var next = rows.map(function (r, i) {
				return i === index ? Object.assign({}, r, { [field]: value }) : r;
			});
			props.onChange(next);
		}

		return el('div', { className: 'bsseo-sidebar-sources' },
			el('label', { className: 'components-base-control__label' }, props.label),
			rows.map(function (row, index) {
				return el('div', { key: index, style: { marginBottom: '8px', display: 'flex', gap: '8px', flexWrap: 'wrap' } },
					el('input', {
						type: 'text',
						className: 'components-text-control__input',
						placeholder: i18n.sourceTitle || 'Titel',
						value: row.title || '',
						onChange: function (e) { updateRow(index, 'title', e.target.value); },
						style: { flex: '1 1 120px' },
					}),
					el('input', {
						type: 'url',
						className: 'components-text-control__input',
						placeholder: 'https://',
						value: row.url || '',
						onChange: function (e) { updateRow(index, 'url', e.target.value); },
						style: { flex: '1 1 160px' },
					})
				);
			})
		);
	}

	function BSseoSidebarPanel() {
		return el(PluginDocumentSettingPanel, {
			name: 'bsseo-panel',
			title: 'BSseo',
			className: 'bsseo-document-setting-panel',
		}, el(BSseoSidebarContent));
	}

	registerPlugin('bsseo-sidebar', {
		render: BSseoSidebarPanel,
		icon: null,
	});
})();
