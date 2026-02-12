(function () {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { ToggleControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;

    const JamesSeoAutoLinkerToggle = () => {
        const meta = useSelect(select => select('core/editor').getEditedPostAttribute('meta') || {});
        const { editPost } = useDispatch('core/editor');

        const isDisabled = meta['_james_seo_auto_linker_disabled'] === '1';

        return React.createElement(
            PluginDocumentSettingPanel,
            {
                name: 'james-seo-auto-linker-panel',
                title: __('SEO Auto Linker', 'james-seo-auto-linker'),
                className: 'james-seo-auto-linker-panel'
            },
            React.createElement(
                ToggleControl,
                {
                    label: __('Disable Auto-Linking', 'james-seo-auto-linker'),
                    help: __('Check this to prevent any automatic links from being generated in this post.', 'james-seo-auto-linker'),
                    checked: isDisabled,
                    onChange: (value) => {
                        editPost({
                            meta: { ...meta, _james_seo_auto_linker_disabled: value ? '1' : '0' }
                        });
                    }
                }
            )
        );
    };

    registerPlugin('james-seo-auto-linker', {
        render: JamesSeoAutoLinkerToggle,
        icon: 'admin-links'
    });
})();
