import $ from 'jquery';
import Finder from '../utils/finder';

let XHRUUID = 0;
const GRAV_CONFIG = typeof global.GravConfig !== 'undefined' ? global.GravConfig : global.GravAdmin.config;

export const Instances = {};

const isInViewport = (elem) => {
    const bounding = elem.getBoundingClientRect();
    const titlebar = document.querySelector('#titlebar');
    const offset = titlebar ? titlebar.getBoundingClientRect().height : 0;
    return (
        bounding.top >= offset &&
        bounding.left >= 0 &&
        bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        bounding.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
};

export class FlexPages {
    constructor(container, data) {
        this.container = $(container);
        this.data = data;
        const dataLoad = this.dataLoad;

        this.finder = new Finder(
            this.container,
            (parent, callback) => {
                return dataLoad.call(this, parent, callback);
            },
            {
                labelKey: 'title',
                defaultPath: '',
                itemTrigger: '[data-flexpages-expand]',
                createItem: function(item) {
                    return FlexPages.createItem(this.config, item, this);
                },
                createItemContent: function(item) {
                    return FlexPages.createItemContent(this.config, item, this);
                }
            }
        );

        /*
        this.finder.$emitter.on('leaf-selected', (item) => {
            console.log('selected', item);
            this.finder.emit('create-column', () => this.createSimpleColumn(item));
        });

        this.finder.$emitter.on('item-selected', (selected) => {
            console.log('selected', selected);
            // for future use only - create column-card creation for file with details like in macOS finder
            // this.finder.$emitter('create-column', () => this.createSimpleColumn(selected));
        }); */

        this.finder.$emitter.on('column-created', () => {
            this.container[0].scrollLeft = this.container[0].scrollWidth - this.container[0].clientWidth;
        });
    }

    static createItem(config, item, finder) {
        const listItem = $('<li />');
        const listItemClasses = [config.className.item];
        const href = `${GRAV_CONFIG.current_url}/${item.route.raw}`.replace('//', '/');
        const link = $('<a />');
        const createItemContent = config.createItemContent || finder.createItemContent;
        const fragment = createItemContent.call(this, item);
        link.append(fragment)
            .attr('href', href)
            .attr('tabindex', -1);

        if (item.url) {
            link.attr('href', item.url);
            listItemClasses.push(item.className);
        }

        if (item[config.childKey]) {
            listItemClasses.push(config.className[config.childKey]);
        }

        listItem.addClass(listItemClasses.join(' '));
        listItem.append(link)
            .attr('data-fjs-item', item[config.itemKey]);

        listItem[0]._item = item;

        return listItem;
    }

    static createItemContent(config, item) {
        const frag = document.createDocumentFragment();
        const icon = $(`<span class="fjs-icon ${item.icon} badge-${item.extras && item.extras.published ? 'published' : 'unpublished'}" />`);
        const route = `${GRAV_CONFIG.current_url}/${item.route.raw}`.replace('//', '/');

        if (item.extras && item.extras.lang) {
            let status = '';
            if (item.extras.translated) {
                status = 'translated';
            }

            if (item.extras.lang === 'n/a') {
                status = 'not-available';
            }

            const lang = $(`<span class="badge-lang ${status}">${item.extras.lang}</span>`);
            lang.appendTo(icon);
        }

        const info = $(`<span class="fjs-info"><b>${item.title}</b> <em>${item.route.display}</em></span>`);
        const actions = $('<span class="fjs-actions" />');

        if (item.child_count) {
            const count = $(`<span class="child-count">${item.child_count}</span>`);
            count.appendTo(actions);
        }

        if (item.extras) {
            const LANG_URL = $('[data-lang-url]').data('langUrl');
            const dotdotdot = $('<div class="button-group" data-flexpages-dotx3 data-flexpages-prevent><button class="button dropdown-toggle" data-toggle="dropdown"><i class="fa fa-ellipsis-v fjs-action-toggle"></i></button></div>');
            dotdotdot.appendTo(actions);
            dotdotdot.on('click', (event) => {
                if (!dotdotdot.find('.dropdown-menu').length) {
                    let tags = '';
                    let langs = '';

                    item.extras.tags.forEach((tag) => {
                        tags += `<span class="badge tag tag-${tag}">${tag}</span>`;
                    });

                    const translations = item.extras.langs || {};
                    Object.keys(translations).forEach((lang) => {
                        const translated = translations[lang];
                        langs += `<a class="lang" href="${LANG_URL.replace(/%LANG%/g, lang).replace('//', '/')}${item.route.raw}"><span class="badge lang-${lang ? lang : 'default'} lang-${translated ? 'translated' : 'non-translated'}">${lang ? lang : 'default'}</span></a>`;
                    });

                    const canPreview = !(item.extras.tags.includes('non-routable') || item.extras.tags.includes('unpublished'));
                    const ul = $(`<div class="dropdown-menu">
    <div class="action-bar">
        <a href="#delete" data-remodal-target="delete" data-delete-url="${route}/task:delete/admin-nonce:${GRAV_CONFIG.admin_nonce}" class="dropdown-item" title="Delete"><i class="fa fa-fw fa-trash"></i></a></li>
        <a href="#" class="dropdown-item" title="Move"><i class="fa fa-fw fa-arrows"></i></a></li>
        <a href="#" class="dropdown-item" title="Duplicate"><i class="fa fa-fw fa-copy"></i></a></li>
        <a href="${route}" class="dropdown-item" title="Edit"><i class="fa fa-fw fa-pencil"></i></a></li>
        ${canPreview ? `<a href="${route}/?preview=1" class="dropdown-item" title="Preview"><i class="fa fa-fw fa-eye"></i></a></li>` : ''}
    </div>
    <div class="divider"></div>
    <div class="tags">${tags}</div>
    <div class="divider"></div>
    ${item.extras.lang || typeof item.extras.langs !== 'undefined' ? `<div class="langs">${langs}</div><div class="divider"></div>` : ''}
    <div class="details">
        <div class="infos">
            <table>
                <tr>
                    <td><b>route</b></td>
                    <td>${item.route.display}</td>
                </tr>
                <tr>
                    <td><b>template</b></td>
                    <td>${item.extras.template}</td>
                </tr>
                <tr>
                    <td><b>modified</b></td>
                    <td>${item.modified}</td>
                </tr>
            </table>
        </div>
    </div>
</div>`);
                    ul.appendTo(dotdotdot);
                }
            });
        }

        if (item.child_count) {
            const arrow = $('<i class="fa fa-chevron-right fjs-children" data-flexpages-expand data-flexpages-prevent></i>');
            arrow.appendTo(actions);
        }

        icon.appendTo(frag);
        info.appendTo(frag);
        actions.appendTo(frag);

        return frag;
    }

    static createLoadingColumn() {
        return $(`
            <div class="fjs-col leaf-col" style="overflow: hidden;">
                <div class="leaf-row">
                    <div class="grav-loading"><div class="grav-loader">Loading...</div></div>
                </div>
            </div>
        `);
    }

    static createErrorColumn(error) {
        return $(`
            <div class="fjs-col leaf-col" style="overflow: hidden;">
                <div class="leaf-row error">
                    <i class="fa fa-fw fa-warning"></i>
                    <span>${error}</span>
                </div>
            </div>
        `);
    }

    createSimpleColumn(item) {}

    dataLoad(parent, callback) {
        if (!parent) {
            return callback(this.data);
        }

        if (!parent.child_count) {
            return false;
        }

        const UUID = ++XHRUUID;
        this.startLoader();

        $.ajax({
            url: `${GRAV_CONFIG.current_url}`,
            method: 'post',
            data: Object.assign({}, {
                route: b64_encode_unicode(parent.route.raw),
                action: 'listLevel'
            }),
            success: (response) => {
                this.stopLoader();

                if (response.status === 'error') {
                    this.finder.$emitter.emit('create-column', FlexPages.createErrorColumn(response.message)[0]);
                    return false;
                }
                // stale request
                if (UUID !== XHRUUID) {
                    return false;
                }

                return callback(response.data);
            }
        });
    }

    startLoader() {
        this.loadingIndicator = FlexPages.createLoadingColumn();
        this.finder.$emitter.emit('create-column', this.loadingIndicator[0]);

        return this.loadingIndicator;
    }

    stopLoader() {
        return this.loadingIndicator && this.loadingIndicator.remove();
    }
}

export const b64_encode_unicode = (str) => {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
};

export const b64_decode_unicode = (str) => {
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
};

const updatePosition = (scrollingColumn) => {
    const group = document.querySelector('#pages-columns .button-group.open');
    if (group) {
        const button = group.querySelector('[data-toggle="dropdown"]');
        const dropdown = group.querySelector('.dropdown-menu');
        const buttonInView = isInViewport(button);

        if (button && dropdown) {
            if (!buttonInView) {
                $(dropdown).css({ display: 'none' });
            } else {
                $(dropdown).css({display: 'inherit'});

                const buttonClientRect = button.getBoundingClientRect();
                const dropdownClientRect = dropdown.getBoundingClientRect();
                const scrollTop = (window.pageYOffset || document.documentElement.scrollTop);
                const scrollLeft = (window.pageXOffset || document.documentElement.scrollLeft);
                const top = buttonClientRect.height + buttonClientRect.top + scrollTop;
                const left = buttonClientRect.right - dropdownClientRect.width + scrollLeft;

                $(dropdown).css({top, left});

                if (scrollingColumn) {
                    const targetClientRect = event.target.getBoundingClientRect();
                    if ((top < targetClientRect.top + scrollTop) || (top > targetClientRect.top + scrollTop + targetClientRect.height)) {
                        $(dropdown).css({display: 'none'});
                    }
                }
            }
        }
    }
};

const closeGhostDropdowns = () => {
    const opened = document.querySelectorAll('#pages-columns .button-group:not(.open) .dropdown-menu') || [];
    opened.forEach((item) => { item.style.display = 'none'; });
};

document.addEventListener('scroll', (event) => {
    if (event.target && !event.target.classList) { return true; }
    const scrollingDocument = event.target.classList.contains('gm-scroll-view');
    const scrollingColumn = event.target.classList.contains('fjs-col');
    if (scrollingDocument || scrollingColumn) {
        closeGhostDropdowns();
        updatePosition(scrollingColumn);
    }
}, true);

document.addEventListener('click', (event) => {
    closeGhostDropdowns();
    if (event.target.dataset.toggle || event.target.closest('[data-toggle="dropdown"]')) {
        // const scrollEvent = new Event('scroll');
        // document.dispatchEvent(scrollEvent);
        (document.querySelectorAll('.gm-scroll-view') || []).forEach((scroll) => {
            const scrollEvent = new Event('scroll');
            scroll.dispatchEvent(scrollEvent);
        });
    }

    // const dropdown = (event.target.classList && event.target.classList.contains('dropdown-menu')) ? event.target : event.target.closest('.dropdown-menu');
    if ((event.target.classList && event.target.classList.contains('dropdown-menu')) || (event.target.closest('.dropdown-menu'))) {
        if (!$(event.target).closest('.dropdown-menu').find(event.target).length) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
});
