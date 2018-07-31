import actions from "../actions"
import _ from "lodash"
/**
 * Function to update participant object
 * @param data
 * @param elements
 */
function updateParticipant(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}

/**
 * Function to update shape
 * @param data
 * @param elements
 */
function updateShape(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}

/**
 * Function to create participant
 * @param data
 * @param elements
 * @param arrayElements
 * @param processes
 * @param collaborations
 */
function createParticipant(data, elements, arrayElements, processes, collaborations) {
    let process = {
        "type": "element",
        "name": "bpmn:participant",
        "attributes": {"id": data.id, "name": "", "processRef": ""},
        "elements": []
    }

    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNShape",
        "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
        "elements": [{
            "type": "element",
            "name": "dc:Bounds",
            "attributes": data.bounds,
            "elements": []
        }]
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    collaborations.push(process)
}

/**
 * Function to create shape
 * @param data
 * @param elements
 * @param arrayElements
 * @param processes
 */
function createShape(data, elements, arrayElements, processes) {
    let eventDefinition = data.eventDefinition ? createEventDefinition(data.eventDefinition) : null
    let arrEvent = eventDefinition ? [eventDefinition] : []
    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNShape",
        "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
        "elements": [{
            "type": "element",
            "name": "dc:Bounds",
            "attributes": data.bounds,
            "elements": []
        }]
    }

    let process = {
        "type": "element",
        "name": "bpmn:" + data.type,
        "attributes": {"id": data.id},
        "elements": arrEvent
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    processes[0].elements.push(process)
}

/**
 * Function to create object definition
 * @param def
 * @returns {{elements: Array, name: string, type: string}}
 */
function createEventDefinition(def) {
    let event = {
        elements: [],
        name: "bpmn:" + def,
        type: "element"
    }
    return event
}

/**
 * Function to create flow in model
 * @param data
 * @param elements
 * @param arrayElements
 * @param processes
 */
function createFlow(data, elements, arrayElements, processes) {
    let bounds = createBounds(data.bounds)

    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNEdge",
        "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
        "elements": bounds
    }

    let process = {
        "type": "element",
        "name": "bpmn:sequenceFlow",
        "attributes": {"id": data.id, "sourceRef": data.sourceRef, "targetRef": data.targetRef},
        "elements": []
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    processes[0].elements.push(process)
}

/**
 * Function to update flow in model
 * @param data
 * @param elements
 */
function updateFlow(data, elements) {
    if (elements[data.id]) {
        elements[data.id].process.attributes = {
            "id": data.id,
            "sourceRef": data.sourceRef,
            "targetRef": data.targetRef
        }

        elements[data.id].diagram.attributes = {
            "id": data.id + "_di",
            "bpmnElement": data.id
        }

        elements[data.id].diagram.elements = createBounds(data.bounds)
    }
}

/**
 * Function to return object bounds
 * @param data
 * @returns {Array}
 */
function createBounds(data) {
    let bounds = []
    _.each(data, (value) => {
        bounds.push({
            "type": "element",
            "name": "di:waypoint",
            "attributes": value,
            "elements": []
        })
    })
    return bounds
}

export default {
    [actions.bpmn.shape.update]: updateShape,
    [actions.bpmn.participant.update]: updateParticipant,
    [actions.bpmn.flow.update]: updateFlow,
    [actions.bpmn.shape.create]: createShape,
    [actions.bpmn.flow.create]: createFlow,
    [actions.bpmn.participant.create]: createParticipant
}
