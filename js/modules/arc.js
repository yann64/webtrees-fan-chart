/**
 * See LICENSE.md file for further details.
 */
import * as d3 from "./d3";
import Gradient from "./gradient";
import Click from "./arc/click";
import Person from "./person";

/**
 * The class handles the creation of the person group and the person elements of the chart. It assignes
 * the click handler and the color group on to of each person.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/ancestral-fan-chart/
 */
export default class Arc
{
    /**
     * Constructor.
     *
     * @param {Config}    config    The configuration
     * @param {Options}   options
     * @param {Hierarchy} hierarchy
     */
    constructor(config, options, hierarchy)
    {
        this._config    = config;
        this._options   = options;
        this._hierarchy = hierarchy;

        this.init();
    }

    /**
     * Create the arc elements for each individual in the data list.
     *
     * @return {void}
     *
     * @public
     */
    init()
    {
        let personGroup = this._config.svg.select("g.personGroup");
        let gradient    = new Gradient(this._config, this._options);

        personGroup.selectAll("g.person")
            .data(this._hierarchy.nodes)
            .enter()
            .each(entry => {
                let person = personGroup
                    .append("g")
                    .attr("class", "person")
                    .attr("id", "person-" + entry.data.id)
                    .on("click", null);

                new Person(this._config, this._options, this._hierarchy, person, entry);

                if (this._options.showColorGradients) {
                    gradient.init(entry);
                }
            });

        let click = new Click(this._config, this._options, this._hierarchy);
        click.bindClickEventListener();

        gradient.addColorGroup(this._hierarchy)
            .style("opacity", 1);
    }
}
