<!--
  - Copyright Enalean (c) 2018. All rights reserved.
  -
  - Tuleap and Enalean names and logos are registrated trademarks owned by
  - Enalean SAS. All other trademarks or names are properties of their respective
  - owners.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

(<template>
    <tr>
        <td>
            <widget-link-to-artifact
                v-bind:artifact="artifact"
            />
        </td>
        <td>{{ project.label }}</td>
        <td class="tlp-table-cell-numeric">
            {{ getFormattedAggregatedTime() }}
        </td>
        <td class="tlp-table-cell-actions timetracking-details-link-to-open-modal"
            v-on:click="show_modal">
            {{ show_times_label }}
        </td>
        <widget-modal-times
            v-bind:key="times.id"
            v-bind:time-data="times"
            v-bind:total-time="getFormattedAggregatedTime()"
        />
    </tr>
</template>)
(<script>
import { formatMinutes } from "../time-formatters.js";
import WidgetModalTimes from "./modal/WidgetModalTimes.vue";
import { gettext_provider } from "../gettext-provider.js";
import { modal as createModal } from "tlp";
import WidgetLinkToArtifact from "./WidgetLinkToArtifact.vue";

export default {
    name: "WidgetArtifactTableRow",
    components: {
        WidgetLinkToArtifact,
        WidgetModalTimes
    },
    props: {
        timeData: Array
    },
    data() {
        const data = this.timeData[0];

        return {
            artifact: data.artifact,
            project: data.project,
            times: this.timeData,
            modal_simple_content: null
        };
    },
    computed: {
        show_times_label: () => gettext_provider.gettext("Details")
    },
    methods: {
        getFormattedAggregatedTime() {
            const minutes = this.timeData.reduce((sum, { minutes }) => minutes + sum, 0);

            return formatMinutes(minutes);
        },
        show_modal() {
            this.modal_simple_content.toggle();
        }
    },
    mounted() {
        const modal = document.getElementById(
            "timetracking-artifact-details-modal-" + this.artifact.id
        );
        this.modal_simple_content = createModal(modal);
    }
};
</script>)
