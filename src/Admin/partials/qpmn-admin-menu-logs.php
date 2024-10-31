<?php

use QPMN\Partner\Qpmn_i18n;
?>
<div class="wrap qpmn-admin qpmn-bootstrap">
    <form method="POST" action="">
        <div class="container">
            <div id="app">
                <div>
                    <?php
                    Qpmn_i18n::_e('Time period in') ?>
                    <select v-on:change="selectedDaysOnChange($event)" v-model="days">
                        <option v-for="o in options" :value="o"> {{o}} <?php Qpmn_i18n::_e('Day(s)'); ?></option>
                    </select>

                </div>
                <div><?php Qpmn_i18n::_e('Click') ?> <a href="<?php echo admin_url("admin.php?page=qpmn_options&step=3"); ?>"><?php Qpmn_i18n::_e('here'); ?></a> <?php Qpmn_i18n::_e('to enable/disable debug log') ?>.</div>
                <div v-if="showLoading" class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only"><?php Qpmn_i18n::_e('Loading'); ?>...</span>
                    </div>
                </div>
                <div v-if="!showLoading">
                    <pre v-if="output.length > 0" class='logs'>
                        <p v-for="o in output"> {{o.log}} </p>
                    </pre>
                    <pre v-else>
                        <p><?php Qpmn_i18n::_e('No logs found') ?></p>
                    </pre>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    Vue.use(Vuex);
    const store = new Vuex.Store({
        state: {
            days: 1,
            output: [],
            showLoading: false
        },
        mutations: {
            changeShowLoading: function(state, payload) {
                state.showLoading = payload;
            },
            changeDays: function(state, payload) {
                state.days = payload;
            },
            changeOutput: function(state, payload) {
                state.output = payload;
            }
        },
        actions: {
            getByDays: function(context, payload) {
                context.commit('changeShowLoading', true);
                instance.get('wc/log', {
                    params: {
                        days: payload.days
                    }
                }).then(function(response) {
                    context.commit('changeOutput', response.data);
                    context.commit('changeShowLoading', false);
                }).catch(function(error) {
                    context.commit('changeShowLoading', false);
                });
            }
        }
    });
    var instance = axios.create({
        baseURL: qpmn_admin_obj.ajax_url,
        headers: {
            'X-WP-Nonce': qpmn_admin_obj.nonce
        }
    });


    new Vue({
        el: "#app",
        data: {
            options: [
                1, 7, 14, 30
            ],
        },
        computed: {
            showLoading: {
                set(val) {
                    store.commit('changeShowLoading', val);
                },
                get() {
                    return store.state.showLoading;
                }
            },
            days: {
                set(val) {
                    store.commit('changeDays', val);
                },
                get() {
                    return store.state.days;
                }
            },
            output: {
                set(val) {
                    store.commit('changeOutput', val);
                },
                get() {
                    return store.state.output;
                }
            }

        },
        methods: {
            getByDays: function() {
                store.dispatch('getByDays', {
                    days: this.days
                });
            },
            selectedDaysOnChange: function(event) {
                store.commit('changeDays', event.target.value);
                this.getByDays();
            }
        },
        mounted: function() {
            this.getByDays();
        }
    });
</script>

<style>
    @import 'https://fonts.googleapis.com/css?family=Roboto';

    body {
        padding: 0;
        margin: 0;
        background-color: #fff;
        font-family: "Roboto", sans-serif;
    }

    .log {
        max-height: 80hv;
    }

    .logs p {
        margin-bottom: 1px;
        line-height: 1;
    }
</style>