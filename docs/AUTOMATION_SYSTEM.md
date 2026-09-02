# Teyaqi Automation & Broadcast System

## Overview

The Teyaqi Automation & Broadcast System is a workflow automation engine inside the Teyaqi Admin Dashboard.

It allows administrators to visually build workflows that react to Teyaqi events and execute a sequence of actions.

Basic workflow:

    Trigger
       ↓
    Condition
       ↓
    Action
       ↓
    Delay
       ↓
    End

The long-term goal is to support automated player communication, broadcasts, engagement campaigns, rewards, notifications, and other event-driven operations.

---

# 1. High-Level Architecture

```text
┌───────────────────────────┐
│        Teyaqi Game        │
│                           │
│ Daily Challenge           │
│ Streak System             │
│ XP System                 │
│ Player Events             │
└─────────────┬─────────────┘
              │
              │ Domain Event
              ▼
┌───────────────────────────┐
│       Laravel Event       │
│                           │
│ StreakReached             │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│ AutomationTriggerDispatcher│
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│ AutomationTriggerManager  │
│                           │
│ Finds matching automations│
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│    AutomationEngine       │
│                           │
│ Creates execution         │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│   AutomationExecutor      │
│                           │
│ Traverses workflow graph  │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│       NodeExecutor        │
│                           │
│ Executes individual nodes │
└─────────────┬─────────────┘
              │
      ┌───────┼────────┐
      ▼       ▼        ▼
   Trigger Condition Telegram
                       Message
                         │
                         ▼
                        End
2. Core Concepts

The system is built around four core concepts:

Automation
Node
Connection
Execution
Automation

An Automation is the complete workflow definition.

It contains:

name
description
status
version
settings
nodes
connections
run statistics

Possible statuses:

draft
active
paused
3. Automation Nodes

Current supported nodes:

trigger
condition
telegram_message
delay
end
Trigger

Starts a workflow when a registered event occurs.

Example:

{
    "event": "streak_reached",
    "enabled": true
}

The trigger identifies when an automation should start.

Condition

Evaluates data in the execution context.

Example:

Field: streak
Operator: greater_than_or_equal
Value: 7

Equivalent to:

streak >= 7

Condition outputs:

true
false

Example:

          ┌── true ──► Telegram
Condition ┤
          └── false ─► End

Supported operators currently include:

equals
not_equals
greater_than
greater_than_or_equal
less_than
less_than_or_equal
contains
not_contains
is_empty
is_not_empty
Telegram Message

Prepares a personalized Telegram message.

Example:

🔥 Hey {{name}}! You reached a {{streak}} day streak!

Variables are resolved from the execution context:

{{name}}
{{streak}}
{{total_xp}}
{{current_streak}}
{{username}}

Current implementation prepares the message.

Actual Telegram delivery is the next major phase.

Delay

Represents a pause in the workflow.

Configuration:

duration
unit

Supported units:

seconds
minutes
hours

Delayed execution will be expanded later using the queue/scheduler system.

End

Terminates a workflow branch.

Example statuses:

success
failed
4. Node Registry

The central node definition registry is:

app/Services/Automation/NodeRegistry.php

It defines each node's:

component
type
name
description
category
icon
color
handles
configuration

Example:

condition
├── input
├── true
└── false

The Node Registry should be the central source of truth for available automation components.

New node types should be added through the registry and their own node handler rather than adding large conditional blocks inside the executor.

5. Visual Automation Builder

The Admin Automation Builder allows administrators to:

Create nodes
Move nodes
Select nodes
Configure nodes
Connect nodes
Delete nodes
Save workflows

The builder stores node positions:

{
    "x": 410,
    "y": 160
}

Connections:

{
    "source_node_id": 2,
    "target_node_id": 3,
    "source_handle": "true",
    "target_handle": "input"
}

The builder contains:

Node Library
Canvas
Configuration Panel
Connection System
Save System
6. Builder View Structure

Current view structure:

resources/views/admin/automations/

├── builder.blade.php
│
└── builder/
    ├── canvas.blade.php
    ├── library.blade.php
    ├── config-panel.blade.php
    │
    └── configs/
        ├── common.blade.php
        ├── trigger.blade.php
        ├── condition.blade.php
        ├── telegram-message.blade.php
        ├── delay.blade.php
        └── end.blade.php

The large builder view has been split into smaller partials to keep the system maintainable.

JavaScript functionality is also being separated into logical modules.

7. Workflow Graph

The workflow is represented as a directed graph.

Current test automation:

Trigger #1
    │
    ▼
Condition #2
    │
    ├── true ─────► Telegram #3
    │                   │
    │                   ▼
    │                Success #4
    │
    └── false ────► Failed #5

Database:

automation_nodes
automation_connections

Connection structure:

automation_id
source_node_id
target_node_id
source_handle
target_handle
metadata
8. Saving a Workflow

When the admin saves the workflow:

Browser
   ↓
PUT /admin/automations/{automation}
   ↓
AutomationController@update
   ↓
Validate
   ↓
Update Automation
   ↓
Create / update Nodes
   ↓
Map temporary frontend IDs
   ↓
Delete removed Nodes
   ↓
Rebuild Connections
   ↓
Commit Transaction
   ↓
Return normalized workflow

Temporary frontend IDs such as:

temp_123456

are mapped to real database node IDs.

This allows newly created nodes and their connections to be saved together.

9. Trigger Architecture

Triggers are event-driven.

Current trigger:

streak_reached

Architecture:

Teyaqi Business Logic
        ↓
StreakReached Event
        ↓
DispatchStreakReachedAutomation
        ↓
AutomationTriggerDispatcher
        ↓
AutomationTriggerManager
        ↓
Matching Active Automations
        ↓
AutomationEngine
10. StreakReached Event

Location:

app/Events/Player/StreakReached.php

The event contains:

$user
$streak

Example:

event(
    new StreakReached(
        $user,
        7
    )
);

The event implements:

ShouldDispatchAfterCommit

This ensures automation execution happens only after the database transaction containing the streak update successfully commits.

11. Real Streak Integration

The event is connected to:

app/Services/StreakService.php

The real production flow is:

Player completes Daily Challenge
        ↓
DailyChallengeController
        ↓
StreakService::updateStreak()
        ↓
Streak increments
        ↓
UserStreak saved
        ↓
User current_streak synced
        ↓
StreakReached
        ↓
Automation System

The event fires only when the streak actually increases.

Example:

5 → 6  = StreakReached(6)
6 → 7  = StreakReached(7)
7 → 8  = StreakReached(8)

Playing multiple times on the same day does not trigger another streak event.

A broken streak resets to 1 and does not count as a continued streak increase.

12. Trigger Dispatcher

Current location:

app/Services/Automation/Triggers/AutomationTriggerDispatcher.php

The dispatcher converts a domain event into an automation trigger.

Example:

trigger:
    streak_reached

data:
    streak = 7

Context can contain:

{
    "user_id": 1,
    "name": "Aman",
    "streak": 7,
    "current_streak": 7,
    "best_streak": 8,
    "total_xp": 14375,
    "telegram_id": "133",
    "username": "amangi"
}
13. Automation Execution

When a trigger matches an active automation, an execution is created.

Model:

AutomationExecution

Important fields:

automation_id
execution_id
trigger_type
trigger_data
status
context
result
error_message
started_at
completed_at
duration_ms

Execution statuses:

running
pending
completed
failed

Example:

Execution ID:
72d63ea3-9af3-4440-b1cb-3e6ac0a35234

Status:
completed
14. Node Execution History

Each node execution is recorded.

Model:

AutomationNodeExecution

It records:

execution_id
node_id
status
input
output
error_message
started_at
completed_at
duration_ms

This allows the system to answer:

Which nodes executed?
Which node failed?
How long did it take?
What input did it receive?
What did it produce?
15. Automation Executor

Location:

app/Services/Automation/AutomationExecutor.php

The executor traverses the workflow graph.

Normal nodes use:

output

and continue to the next node.

Condition nodes use:

true
false

and choose the appropriate branch.

Example:

Trigger
   │ output
   ▼
Condition
   ├── true ──► Telegram
   │
   └── false ─► Failed End

The executor uses the persisted automation_connections table as the workflow graph source of truth.

16. Node Executor

The NodeExecutor executes individual nodes.

Conceptually:

AutomationExecutor
       ↓
NodeExecutor
       ↓
TriggerNode
ConditionNode
TelegramMessageNode
DelayNode
EndNode

Each node is responsible for its own behavior.

17. Successful End-to-End Test

The following automation has been successfully executed:

Trigger
    ↓
Condition: streak >= 7
    ↓ true
Telegram Message
    ↓
Success End

A real StreakReached event was dispatched.

The automation execution completed successfully.

Execution context included:

{
    "streak": 7,
    "condition": {
        "field": "streak",
        "actual": 7,
        "expected": 7,
        "operator": "greater_than_or_equal",
        "result": true
    },
    "telegram": {
        "message": "🔥 Hey Aman! You reached a 7 day streak!",
        "status": "prepared"
    },
    "completed": true
}

This proves the current engine can successfully perform:

Real Event
    ↓
Trigger Matching
    ↓
Workflow Execution
    ↓
Condition Evaluation
    ↓
Branch Selection
    ↓
Telegram Message Preparation
    ↓
Workflow Completion
18. Current Telegram Limitation

The Telegram node currently returns:

status = prepared

The message is correctly generated and variables are replaced.

Example:

🔥 Hey Aman! You reached a 7 day streak!

However, the automation node does not yet send the message through Telegram.

The next Telegram phase will implement:

Prepared
    ↓
Telegram API
    ↓
Sent

with:

success
failed
retrying

states.

19. Current File Structure

Relevant backend architecture:

app/
├── Events/
│   └── Player/
│       └── StreakReached.php
│
├── Listeners/
│   └── DispatchStreakReachedAutomation.php
│
├── Models/
│   ├── Automation.php
│   ├── AutomationNode.php
│   ├── AutomationConnection.php
│   ├── AutomationExecution.php
│   └── AutomationNodeExecution.php
│
├── Services/
│   ├── StreakService.php
│   │
│   └── Automation/
│       ├── AutomationEngine.php
│       ├── AutomationExecutor.php
│       ├── NodeExecutor.php
│       ├── NodeRegistry.php
│       │
│       ├── Nodes/
│       │   ├── TriggerNode.php
│       │   ├── ConditionNode.php
│       │   ├── TelegramMessageNode.php
│       │   ├── DelayNode.php
│       │   └── EndNode.php
│       │
│       └── Triggers/
│           ├── AutomationTrigger.php
│           ├── AutomationTriggerDispatcher.php
│           ├── AutomationTriggerManager.php
│           └── TriggerTypes.php
│
└── Http/
    └── Controllers/
        └── Admin/
            └── AutomationController.php
20. Current Status
Completed
✅ Automation database structure
✅ Automation builder
✅ Node Library
✅ Node configuration
✅ Node movement
✅ Node connections
✅ Connection persistence
✅ Workflow saving
✅ Node Registry
✅ Trigger system
✅ Event-based triggers
✅ StreakReached event
✅ Real StreakService integration
✅ Automation execution
✅ Node execution history
✅ Condition evaluation
✅ Condition branching
✅ Variable replacement
✅ Workflow completion
Not Completed Yet
⬜ Real Telegram delivery
⬜ Telegram delivery status
⬜ Telegram error handling
⬜ Retry system
⬜ Queue-based execution
⬜ Delay execution
⬜ Scheduled execution
⬜ More game/event triggers
⬜ Audience targeting
⬜ Broadcast campaigns
⬜ Rate limiting
⬜ Automation monitoring
⬜ Analytics
⬜ Audit logs
⬜ Campaign reporting