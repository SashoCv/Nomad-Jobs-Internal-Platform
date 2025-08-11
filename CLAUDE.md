# Nomad Jobs Internal Platform - Claude Documentation

## Agent Permissions System

### Permission Types Overview

The system has two distinct permission categories for handling candidates submitted by agents:

#### 1. AGENT_CANDIDATES_* Permissions
**Purpose**: For agents to manage their own candidate operations
**Used by**: Agent users (role_id = 4)
**Scope**: Agent's own candidates only

- `AGENT_CANDIDATES_READ` - Agents can view their own candidates
- `AGENT_CANDIDATES_CREATE` - Agents can add new candidates
- `AGENT_CANDIDATES_UPDATE` - Agents can modify their own candidates
- `AGENT_CANDIDATES_DELETE` - Agents can remove their own candidates

#### 2. CANDIDATES_FROM_AGENT_* Permissions  
**Purpose**: For administrators to view/manage candidates submitted by agents
**Used by**: Admin/Staff roles (General Manager, Manager, etc.)
**Scope**: All candidates from all agents

- `CANDIDATES_FROM_AGENT_READ` - Admins can view all agent-submitted candidates
- `CANDIDATES_FROM_AGENT_CHANGE_STATUS` - Admins can change status of agent candidates
- `CANDIDATES_FROM_AGENT_DELETE` - Admins can delete agent candidates

### AgentCandidateController Endpoint Breakdown

#### Agent Endpoints (Use AGENT_CANDIDATES_* permissions):
- `agentAddCandidateForAssignedJob()` - Agents add candidates → `AGENT_CANDIDATES_CREATE`
- `destroy()` - Agents delete their candidates → `CANDIDATES_FROM_AGENT_DELETE` (Note: Currently uses admin permission, may need review)

#### Admin Endpoints (Use CANDIDATES_FROM_AGENT_* permissions):
- `getAllCandidatesFromAgents()` - Admins view all agent candidates → `CANDIDATES_FROM_AGENT_READ`
- `getCandidatesForAssignedJob($id)` - Admins view candidates for specific job → `CANDIDATES_FROM_AGENT_READ`

### Key Insights

1. **Separation of Concerns**: Agent permissions vs Admin permissions are distinct
2. **Agent Scope**: Agents work with their own candidates only
3. **Admin Scope**: Admins have oversight of all agent submissions
4. **Permission Consistency**: Always match the permission type to the user role and operation scope

### Common Permission Issues Fixed

- **Wrong Permission**: `CANDIDATES_FROM_AGENT_CREATE` → `AGENT_CANDIDATES_CREATE` 
- **Mixed Scopes**: Admin endpoints were using agent permissions
- **Missing Permissions**: Added `AGENT_CANDIDATES_DELETE` to permission model

### Permission Assignment

**Agents receive**:
- `JOB_POSTINGS_READ` - Can view available job postings
- `CANDIDATES_READ` - Can view general candidate info  
- `AGENT_CANDIDATES_*` - Full CRUD on their own candidates
- `DOCUMENTS_READ`, `DOCUMENTS_CREATE` - Document access

**Admins receive**:
- `CANDIDATES_FROM_AGENT_*` - Full oversight of agent submissions
- Plus all other administrative permissions