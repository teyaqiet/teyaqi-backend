<script>
function automationBuilderMedia() {

    return {

        /*
        |--------------------------------------------------------------------------
        | Ensure Telegram Media
        |--------------------------------------------------------------------------
        */

        ensureTelegramMedia(node) {

            if (!node) {
                return null;
            }

            this.ensureNodeConfig(node);

            if (
                !node.config.media ||
                typeof node.config.media !== 'object' ||
                Array.isArray(node.config.media)
            ) {

                node.config.media = {

                    enabled: false,
                    type: 'photo',
                    source: '',
                    caption: '',
                    file_name: '',
                    file_type: '',
                    file_size: 0,
                    preview_url: '',

                };

            }

            return node.config.media;

        },


        /*
        |--------------------------------------------------------------------------
        | Toggle Telegram Media
        |--------------------------------------------------------------------------
        */

        toggleTelegramMedia() {

            const node = this.getSelectedNode();

            if (!node) {
                return;
            }

            const media =
                this.ensureTelegramMedia(node);

            media.enabled =
                !media.enabled;

            this.markDirty();

        },


        /*
        |--------------------------------------------------------------------------
        | Remove Telegram Media
        |--------------------------------------------------------------------------
        */

        removeTelegramMedia() {

            const node =
                this.getSelectedNode();

            if (!node) {
                return;
            }

            const media =
                this.ensureTelegramMedia(node);

            if (media.preview_url) {

                try {

                    URL.revokeObjectURL(
                        media.preview_url
                    );

                } catch (error) {

                    console.warn(
                        '[Telegram Message] Could not revoke preview URL.',
                        error
                    );

                }

            }

            if (
                this.pendingMediaFiles &&
                this.pendingMediaFiles[node.id]
            ) {

                delete this.pendingMediaFiles[node.id];

            }

            media.file_name = '';
            media.file_type = '';
            media.file_size = 0;
            media.preview_url = '';
            media.source = '';

            this.markDirty();

        },

/*
|--------------------------------------------------------------------------
| Add Telegram Button
|--------------------------------------------------------------------------
*/

addTelegramButton() {

    const node =
        this.getSelectedNode();

    if (!node) {
        return;
    }

    if (
        node.type !== 'telegram_message'
    ) {
        return;
    }

    this.ensureNodeConfig(node);

    if (
        !Array.isArray(node.config.buttons)
    ) {

        node.config.buttons = [];

    }

    node.config.buttons.push({

        text: '',
        type: 'url',
        url: '',

    });

    this.markDirty();

},


/*
|--------------------------------------------------------------------------
| Remove Telegram Button
|--------------------------------------------------------------------------
*/

removeTelegramButton(index) {

    const node =
        this.getSelectedNode();

    if (!node) {
        return;
    }

    if (
        node.type !== 'telegram_message'
    ) {
        return;
    }

    this.ensureNodeConfig(node);

    if (
        !Array.isArray(node.config.buttons)
    ) {

        node.config.buttons = [];

        return;

    }

    if (
        index < 0 ||
        index >= node.config.buttons.length
    ) {

        return;

    }

    node.config.buttons.splice(
        index,
        1
    );

    this.markDirty();

},
        /*
        |--------------------------------------------------------------------------
        | Handle Telegram Media Upload
        |--------------------------------------------------------------------------
        */

        handleTelegramMediaUpload(event) {

            const node =
                this.getSelectedNode();

            if (!node) {
                return;
            }

            const file =
                event?.target?.files?.[0];

            if (!file) {
                return;
            }

            const media =
                this.ensureTelegramMedia(node);

            if (media.preview_url) {

                try {

                    URL.revokeObjectURL(
                        media.preview_url
                    );

                } catch (error) {

                    console.warn(
                        '[Telegram Message] Could not revoke previous preview URL.',
                        error
                    );

                }

            }

            media.enabled = true;

            media.file_name =
                file.name || '';

            media.file_type =
                file.type || '';

            media.file_size =
                file.size || 0;


            /*
            |--------------------------------------------------------------------------
            | Detect Telegram Media Type
            |--------------------------------------------------------------------------
            */

            if (file.type.startsWith('image/')) {

                media.type =
                    file.type === 'image/gif'
                        ? 'animation'
                        : 'photo';

            } else if (
                file.type.startsWith('video/')
            ) {

                media.type = 'video';

            } else if (
                file.type.startsWith('audio/')
            ) {

                media.type = 'audio';

            } else if (
                file.name
                    .toLowerCase()
                    .endsWith('.gif')
            ) {

                media.type = 'animation';

            } else {

                media.type = 'document';

            }


            /*
            |--------------------------------------------------------------------------
            | Local Preview
            |--------------------------------------------------------------------------
            */

            media.preview_url =
                URL.createObjectURL(file);


            if (!this.pendingMediaFiles) {

                this.pendingMediaFiles = {};

            }


            this.pendingMediaFiles[node.id] =
                file;


            /*
            |--------------------------------------------------------------------------
            | Uploaded File Is Pending
            |--------------------------------------------------------------------------
            |
            | The actual source will be assigned when the automation
            | is saved.
            |
            */

            media.source = '';

            this.markDirty();

        },

    };
    
    

}
</script>